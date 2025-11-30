<?php

namespace App\Service;

use App\Entity\Cross;
use App\Entity\User;
use App\Repository\CrossRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;

/**
 * Service client pour interagir avec l'API Jira
 *
 * Ce service permet de créer des tickets Jira avec des descriptions,
 * noms et pièces jointes via l'API REST de Jira.
 *
 * @author Votre nom
 */
class JiraClient
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $jiraBaseUrl;
    private string $jiraEmail;
    private string $jiraApiToken;
    private string $defaultProjectKey;
    private string $defaultIssueType;

    /**
     * Constructeur du client Jira
     *
     * @param LoggerInterface $logger Pour logger les opérations et erreurs
     * @param string $jiraBaseUrl URL de base de votre instance Jira (ex: https://votre-domaine.atlassian.net)
     * @param string $jiraEmail Email de l'utilisateur Jira
     * @param string $jiraApiToken Token API généré depuis Jira
     * @param string $defaultProjectKey Clé du projet par défaut (ex: "PROJ")
     * @param string $defaultIssueType Type de ticket par défaut (ex: "Task", "Bug", "Story")
     */
    public function __construct(
        LoggerInterface $logger,
        string $jiraBaseUrl,
        string $jiraEmail,
        string $jiraApiToken,
        private CrossRepository $crossRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        string $defaultIssueType = 'Task',
        string $defaultProjectKey = 'WEB',
    ) {
        $this->logger = $logger;
        $this->jiraBaseUrl = rtrim($jiraBaseUrl, '/');
        $this->jiraEmail = $jiraEmail;
        $this->jiraApiToken = $jiraApiToken;
        $this->defaultProjectKey = $defaultProjectKey;
        $this->defaultIssueType = $defaultIssueType;

        // Création du client HTTP avec authentification basique
        // Jira utilise l'email + API token pour l'authentification
        $this->httpClient = HttpClient::create([
            'auth_basic' => [$this->jiraEmail, $this->jiraApiToken],
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Crée un nouveau ticket Jira
     *
     * Cette méthode utilise l'endpoint /rest/api/3/issue pour créer un ticket.
     * Elle structure les données selon le format attendu par l'API Jira.
     * Adaptée pour votre instance Jira Agrume avec les champs obligatoires.
     *
     * @param string $summary Titre/nom du ticket
     * @param string $description Description détaillée du ticket
     * @param string|null $projectKey Clé du projet (utilise le défaut si null)
     * @param string|null $issueType Type de ticket (utilise le défaut si null)
     * @param string|null $assigneeAccountId Account ID de l'assigné (optionnel)
     * @param array $additionalFields Champs supplémentaires optionnels
     *
     * @return array Données du ticket créé avec son ID
     * @throws \Exception En cas d'erreur lors de la création
     */
    public function createIssue(
        UserInterface $user,
        string $summary,
        string $description,
        ?string $projectKey = null,
        ?string $issueType = null,
        ?string $assigneeAccountId = null,
        ?array $additionalFields = []
    ): array {
        $projectKey = $projectKey ?? $this->defaultProjectKey;
        $issueType = $issueType ?? $this->defaultIssueType;

        // Structure des données selon votre instance Jira Agrume
        // Basée sur l'analyse de votre ticket WEB-67
        $issueData = [
            'fields' => array_merge([
                // Projet - obligatoire
                'project' => [
                    'key' => $projectKey
                ],
                // Résumé - obligatoire
                'summary' => $summary,
                // Description au format ADF (Atlassian Document Format)
                'description' => [
                    'type' => 'doc',
                    'version' => 1,
                    'content' => [
                        [
                            'type' => 'paragraph',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $description
                                ]
                            ]
                        ]
                    ]
                ],
                // Type de ticket - obligatoire
                // Utilise le nom français comme dans votre instance
                'issuetype' => [
                    'name' => $issueType
//                    'name' => "Task"
                ],
                // Priorité par défaut (Medium comme dans votre exemple)
                'priority' => [
                    'name' => 'Medium'
                ]
            ], $additionalFields)
        ];

        // Ajout de l'assigné si spécifié
        if ($assigneeAccountId) {
            $issueData['fields']['assignee'] = [
                'accountId' => $assigneeAccountId
            ];
        }

        $this->logger->info('Création d\'un ticket Jira', [
            'project' => $projectKey,
            'summary' => $summary,
            'issueType' => $issueType
        ]);
        try {
            $response = $this->httpClient->request('POST', $this->jiraBaseUrl . '/rest/api/3/issue', [
                'json' => $issueData
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray();

            if ($statusCode === 201) {
                $this->logger->info('Ticket Jira créé avec succès', [
                    'issueId' => $responseData['id'],
                    'issueKey' => $responseData['key']
                ]);
                $userEntity = $this->userRepository->find($user->getId());
                $cross = (new Cross())->setSender($userEntity)->setCode($responseData['key']);
                $this->entityManager->persist($cross);
                $this->entityManager->flush();
                return $responseData;
            } else {
                throw new \Exception("Erreur lors de la création du ticket: " . $response->getContent(false));
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du ticket Jira', [
                'error' => $e->getMessage(),
                'project' => $projectKey,
                'summary' => $summary
            ]);
            throw $e;
        }
    }

    /**
     * Ajoute une pièce jointe à un ticket existant
     *
     * Cette méthode utilise l'endpoint /rest/api/3/issue/{issueIdOrKey}/attachments
     * Elle gère l'upload de fichiers via une requête multipart/form-data
     *
     * @param string $issueKey Clé du ticket (ex: "PROJ-123")
     * @param UploadedFile $file Fichier uploadé depuis le formulaire
     * @param string|null $filename Nom personnalisé pour le fichier (optionnel)
     *
     * @return array Informations sur la pièce jointe créée
     * @throws \Exception En cas d'erreur lors de l'upload
     */
    public function addAttachment(string $issueKey, UploadedFile $file, ?string $filename = null): array
    {
        // Validation du fichier uploadé
        if (!$file->isValid()) {
            throw new \Exception('Fichier invalide: ' . $file->getErrorMessage());
        }

        // Utilisation du nom original ou du nom personnalisé
        $attachmentName = $filename ?? $file->getClientOriginalName();

        $this->logger->info('Ajout d\'une pièce jointe au ticket', [
            'issueKey' => $issueKey,
            'filename' => $attachmentName,
            'fileSize' => $file->getSize(),
            'mimeType' => $file->getMimeType()
        ]);

        try {
            // Pour les pièces jointes, on doit utiliser multipart/form-data
            // et retirer le Content-Type JSON par défaut
            $response = $this->httpClient->request('POST',
                $this->jiraBaseUrl . '/rest/api/3/issue/' . $issueKey . '/attachments',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-Atlassian-Token' => 'no-check', // Requis par Jira pour les uploads
                        // Pas de Content-Type pour multipart/form-data
                    ],
                    'body' => [
                        'file' => fopen($file->getPathname(), 'r')
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray();

            if ($statusCode === 200) {
                $this->logger->info('Pièce jointe ajoutée avec succès', [
                    'issueKey' => $issueKey,
                    'attachmentId' => $responseData[0]['id'] ?? null,
                    'filename' => $responseData[0]['filename'] ?? null
                ]);

                return $responseData;
            } else {
                throw new \Exception("Erreur lors de l'ajout de la pièce jointe: " . $response->getContent(false));
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'ajout de la pièce jointe', [
                'error' => $e->getMessage(),
                'issueKey' => $issueKey,
                'filename' => $attachmentName
            ]);
            throw $e;
        }
    }

    /**
     * Crée un ticket avec une pièce jointe en une seule opération
     *
     * Cette méthode combine la création du ticket et l'ajout de la pièce jointe.
     * Elle utilise une approche transactionnelle : si l'ajout de la pièce jointe
     * échoue, le ticket reste créé mais une exception est levée.
     *
     * @param string $summary Titre du ticket
     * @param string $description Description du ticket
     * @param UploadedFile $attachment Fichier à attacher
     * @param string|null $projectKey Clé du projet (optionnel)
     * @param string|null $issueType Type de ticket (optionnel)
     *
     * @return array Données complètes du ticket créé avec les informations de la pièce jointe
     * @throws \Exception En cas d'erreur
     */
    public function createIssueWithAttachment(
        UserInterface $user,
        string $summary,
        string $description,
        UploadedFile $attachment,
        ?string $projectKey = null,
        ?string $issueType = null,
        array  $additionalFields = []
    ): array {
        $this->logger->info('Création d\'un ticket avec pièce jointe', [
            'summary' => $summary,
            'attachmentName' => $attachment->getClientOriginalName()
        ]);

        try {
            // Étape 1: Créer le ticket
            $issueData = $this->createIssue( $user,$summary, $description, $projectKey, $issueType,null, $additionalFields);
            $issueKey = $issueData['key'];

            // Étape 2: Ajouter la pièce jointe
            $attachmentData = $this->addAttachment($issueKey, $attachment);

            // Combiner les données pour le retour
            $result = array_merge($issueData, [
                'attachments' => $attachmentData
            ]);

            $this->logger->info('Ticket avec pièce jointe créé avec succès', [
                'issueKey' => $issueKey,
                'attachmentCount' => count($attachmentData)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du ticket avec pièce jointe', [
                'error' => $e->getMessage(),
                'summary' => $summary
            ]);
            throw $e;
        }
    }

    /**
     * Récupère les informations d'un ticket existant
     *
     * Méthode utile pour vérifier l'état d'un ticket ou récupérer ses détails.
     *
     * @param string $issueKey Clé du ticket
     * @return array Données du ticket
     * @throws \Exception En cas d'erreur
     */
    public function getIssue(string $issueKey): array
    {
        try {
            $response = $this->httpClient->request('GET',
                $this->jiraBaseUrl . '/rest/api/3/issue/' . $issueKey
            );

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            } else {
                throw new \Exception("Ticket non trouvé: " . $issueKey);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du ticket', [
                'error' => $e->getMessage(),
                'issueKey' => $issueKey
            ]);
            throw $e;
        }
    }

    /**
     * Teste la connexion à l'API Jira
     *
     * Méthode utile pour vérifier que les credentials et la configuration
     * sont corrects avant de créer des tickets.
     *
     * @return bool True si la connexion est OK
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->jiraBaseUrl . '/rest/api/3/myself');
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->error('Test de connexion Jira échoué', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mappe les types de tickets en anglais vers les noms français de votre instance
     *
     * Basé sur l'analyse de votre instance Jira Agrume
     *
     * @param string $englishType Type en anglais
     * @return string Type en français correspondant
     */
    private function mapIssueTypeToFrench(string $englishType): string
    {
        $mapping = [
            'Task' => 'Tâche',
            'Bug' => 'Bug',
            'Story' => 'Story',
            'Epic' => 'Epic',
            'Sub-task' => 'Sous-tâche'
        ];

        return $mapping[$englishType] ?? 'Tâche'; // Défaut sur "Tâche"
    }

    /**
     * Récupère les métadonnées de création pour un projet
     *
     * Utile pour découvrir les champs obligatoires et les valeurs possibles
     *
     * @param string $projectKey Clé du projet
     * @return array Métadonnées de création
     */
    public function getCreateMeta(string $projectKey): array
    {
        try {
            $response = $this->httpClient->request('GET',
                $this->jiraBaseUrl . '/rest/api/3/issue/createmeta',
                [
                    'query' => [
                        'projectKeys' => $projectKey,
                        'expand' => 'projects.issuetypes.fields'
                    ]
                ]
            );

            if ($response->getStatusCode() === 200) {
                return $response->toArray();
            } else {
                throw new \Exception("Impossible de récupérer les métadonnées pour le projet: " . $projectKey);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des métadonnées de création', [
                'error' => $e->getMessage(),
                'projectKey' => $projectKey
            ]);
            throw $e;
        }
    }

    /**
     * Liste les tickets selon des critères de recherche
     *
     * Utilise l'API de recherche Jira avec JQL (Jira Query Language).
     * Par défaut, liste tous les tickets du projet par défaut.
     *
     * @param string|null $projectKey Clé du projet à filtrer (utilise le défaut si null)
     * @param string|null $jql Requête JQL personnalisée (optionnel, prioritaire sur projectKey)
     * @param int $maxResults Nombre maximum de résultats (défaut: 50)
     * @param int $startAt Index de départ pour la pagination (défaut: 0)
     * @param array $fields Champs à récupérer (défaut: tous les champs navigables)
     *
     * @return array Résultats de la recherche avec 'issues', 'total', 'startAt', 'maxResults'
     * @throws \Exception En cas d'erreur lors de la recherche
     */
    public function listIssues(
        ?string $projectKey = null,
        ?string $jql = null,
        int $maxResults = 50,
        int $startAt = 0,
        array $fields = ['*navigable']
    ): array {
        // Construction de la requête JQL
        if ($jql === null) {
            $projectKey = $projectKey ?? $this->defaultProjectKey;
            $jql = "project = {$projectKey} ORDER BY created DESC";
        }

        $this->logger->info('Recherche de tickets Jira', [
            'jql' => $jql,
            'maxResults' => $maxResults,
            'startAt' => $startAt
        ]);

        try {
            $response = $this->httpClient->request('GET',
                $this->jiraBaseUrl . '/rest/api/3/search',
                [
                    'query' => [
                        'jql' => $jql,
                        'maxResults' => $maxResults,
                        'startAt' => $startAt,
                        'fields' => implode(',', $fields)
                    ]
                ]
            );

            $statusCode = $response->getStatusCode();
            $responseData = $response->toArray();

            if ($statusCode === 200) {
                $this->logger->info('Tickets récupérés avec succès', [
                    'total' => $responseData['total'],
                    'returned' => count($responseData['issues'])
                ]);

                return $responseData;
            } else {
                throw new \Exception("Erreur lors de la recherche de tickets: " . $response->getContent(false));
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche de tickets', [
                'error' => $e->getMessage(),
                'jql' => $jql
            ]);
            throw $e;
        }
    }
}
