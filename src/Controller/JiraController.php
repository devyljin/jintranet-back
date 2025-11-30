<?php

namespace App\Controller;

use App\Service\JiraClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
#[Route('/api/v1/jira', name: 'api_jira_')]
class JiraController extends AbstractController
{
    private JiraClient $jiraClient;
    private LoggerInterface $logger;

    public function __construct(JiraClient $jiraClient, LoggerInterface $logger)
    {
        $this->jiraClient = $jiraClient;
        $this->logger = $logger;
    }

    /**
     * Route de test pour vérifier que l'API fonctionne
     */
    #[Route('/test', name: 'api_test', methods: ['GET', 'POST'])]
    public function apiTest(Request $request): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'API Jira fonctionne !',
            'method' => $request->getMethod(),
            'timestamp' => date('Y-m-d H:i:s'),
            'request_data' => [
                'query' => $request->query->all(),
                'post' => $request->request->all(),
                'files' => count($request->files->all())
            ]
        ]);
    }

    /**
     * Créer un nouveau ticket Jira
     */
//    #[Route('/tickets', name: 'create_ticket', methods: ['POST'])]
//    public function createTicket(Request $request): JsonResponse
//    {
//        $data = json_decode($request->getContent(), true);
//
//        if (!isset($data['summary']) || !isset($data['description'])) {
//            return $this->json([
//                'success' => false,
//                'message' => 'Summary and description are required',
//            ], Response::HTTP_BAD_REQUEST);
//        }
//
//        try {
//            $result = $this->jiraClient->createIssue(
//                $data['summary'],
//                $data['description'],
//                $data['projectKey'] ?? null,
//                $data['issueType'] ?? null,
//                $data['assigneeAccountId'] ?? null,
//                $data['additionalFields'] ?? []
//            );
//
//            return $this->json([
//                'success' => true,
//                'ticket' => [
//                    'key' => $result['key'],
//                    'id' => $result['id'],
//                    'self' => $result['self'],
//                ],
//                'message' => 'Ticket created successfully',
//            ], Response::HTTP_CREATED);
//        } catch (\Exception $e) {
//            return $this->json([
//                'success' => false,
//                'message' => 'Error creating ticket',
//                'error' => $e->getMessage(),
//            ], Response::HTTP_INTERNAL_SERVER_ERROR);
//        }
//    }
    /**
     * Créer un nouveau ticket Jira avec support de plusieurs fichiers
     */
    #[Route('/tickets', name: 'ticket.create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            // Récupération des données du formulaire
            $summary = $request->request->get('summary');
            $description = $request->request->get('description');
            $projectKey = $request->request->get('project_key'); // Optionnel
            $issueType = $request->request->get('issue_type'); // Optionnel

            // Validation des données obligatoires
            $violations = $validator->validate($summary, [
                new Assert\NotBlank(['message' => 'Le titre du ticket est obligatoire']),
                new Assert\Length(['min' => 5, 'max' => 200])
            ]);

            $violations->addAll($validator->validate($description, [
                new Assert\NotBlank(['message' => 'La description du ticket est obligatoire']),
                new Assert\Length(['min' => 10])
            ]));

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[] = $violation->getMessage();
                }
                return $this->json(['success' => false, 'errors' => $errors], 400);
            }

            // Récupération des fichiers (support de plusieurs fichiers)
            $uploadedFiles = $request->files->get('attachments', []);
            $singleFile = $request->files->get('attachment');

            // Normalisation : si un seul fichier est envoyé avec 'attachment', le mettre dans un tableau
            if ($singleFile && $singleFile->isValid()) {
                $uploadedFiles = [$singleFile];
            }

            // Validation du nombre de fichiers
            if (is_array($uploadedFiles) && count($uploadedFiles) > 10) {
                return $this->json([
                    'success' => false,
                    'message' => 'Nombre maximum de fichiers dépassé (10 maximum)'
                ], 400);
            }

            $additionalFields = ["customfield_10114" => $this->getUser()->getUserIdentifier()];

            // Cas 1 : Un seul fichier (utilise la méthode optimisée)
            if (is_array($uploadedFiles) && count($uploadedFiles) === 1 && $uploadedFiles[0]->isValid()) {
                $this->logger->info('Création d\'un ticket avec une pièce jointe', [
                    'summary' => $summary,
                    'filename' => $uploadedFiles[0]->getClientOriginalName()
                ]);

                $result = $this->jiraClient->createIssueWithAttachment(
                    $this->getUser(),
                    $summary,
                    $description,
                    $uploadedFiles[0],
                    $projectKey,
                    $issueType,
                    $additionalFields
                );

                return $this->json([
                    'success' => true,
                    'message' => 'Ticket créé avec succès avec pièce jointe',
                    'ticket' => [
                        'key' => $result['key'],
                        'id' => $result['id'],
                        'url' => $this->generateJiraUrl($result['key']),
                        'attachments' => count($result['attachments'])
                    ]
                ]);
            }

            // Cas 2 : Plusieurs fichiers
            elseif (is_array($uploadedFiles) && count($uploadedFiles) > 1) {
                $this->logger->info('Création d\'un ticket avec plusieurs pièces jointes', [
                    'summary' => $summary,
                    'file_count' => count($uploadedFiles)
                ]);

                // Créer le ticket d'abord
                $result = $this->jiraClient->createIssue(
                    $this->getUser(),
                    $summary,
                    $description,
                    $projectKey,
                    $issueType,
                    null,
                    $additionalFields
                );

                $issueKey = $result['key'];
                $attachmentResults = [];
                $successCount = 0;
                $errorCount = 0;

                // Ajouter chaque fichier
                foreach ($uploadedFiles as $index => $file) {
                    if (!$file->isValid()) {
                        $this->logger->warning('Fichier invalide ignoré', [
                            'index' => $index,
                            'error' => $file->getErrorMessage()
                        ]);
                        $errorCount++;
                        continue;
                    }

                    try {
                        $attachmentData = $this->jiraClient->addAttachment($issueKey, $file);
                        $attachmentResults[] = $attachmentData[0] ?? null;
                        $successCount++;
                    } catch (\Exception $e) {
                        $this->logger->error('Erreur lors de l\'ajout d\'une pièce jointe', [
                            'issueKey' => $issueKey,
                            'filename' => $file->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                        $errorCount++;
                    }
                }

                return $this->json([
                    'success' => true,
                    'message' => sprintf(
                        'Ticket créé avec %d pièce(s) jointe(s) sur %d',
                        $successCount,
                        count($uploadedFiles)
                    ),
                    'ticket' => [
                        'key' => $result['key'],
                        'id' => $result['id'],
                        'url' => $this->generateJiraUrl($result['key']),
                        'attachments_count' => $successCount,
                        'attachments_failed' => $errorCount
                    ]
                ]);
            }

            // Cas 3 : Pas de fichier
            else {
                $this->logger->info('Création d\'un ticket simple', [
                    'summary' => $summary
                ]);

                $result = $this->jiraClient->createIssue(
                    $this->getUser(),
                    $summary,
                    $description,
                    $projectKey,
                    $issueType,
                    null,
                    $additionalFields
                );

                return $this->json([
                    'success' => true,
                    'message' => 'Ticket créé avec succès',
                    'ticket' => [
                        'key' => $result['key'],
                        'id' => $result['id'],
                        'url' => $this->generateJiraUrl($result['key'])
                    ]
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du ticket', [
                'error' => $e->getMessage(),
                'summary' => $summary ?? 'N/A'
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la création du ticket: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Récupérer un ticket par sa clé
     */
    #[Route('/tickets/{ticketKey}', name: 'get_ticket', methods: ['GET'])]
    public function getTicket(string $ticketKey): JsonResponse
    {
        try {
            $ticket = $this->jiraClient->getIssue($ticketKey);

            return $this->json([
                'success' => true,
                'ticket' => [
                    'key' => $ticket['key'],
                    'id' => $ticket['id'],
                    'summary' => $ticket['fields']['summary'],
                    'description' => $this->extractDescriptionText($ticket['fields']['description'] ?? null),
                    'status' => $ticket['fields']['status']['name'] ?? 'Unknown',
                    'priority' => $ticket['fields']['priority']['name'] ?? 'Unknown',
                    'issueType' => $ticket['fields']['issuetype']['name'] ?? 'Unknown',
                    'assignee' => $ticket['fields']['assignee']['displayName'] ?? 'Unassigned',
                    'reporter' => $ticket['fields']['reporter']['displayName'] ?? 'Unknown',
                    'created' => $ticket['fields']['created'] ?? null,
                    'updated' => $ticket['fields']['updated'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching ticket',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Tester la connexion Jira
     */
    #[Route('/connection', name: 'test_connection', methods: ['GET'])]
    public function testConnection(): JsonResponse
    {
        try {
            $isConnected = $this->jiraClient->testConnection();

            return $this->json([
                'success' => $isConnected,
                'message' => $isConnected ? 'Connection successful' : 'Connection failed',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupérer les métadonnées de création
     */
    #[Route('/metadata', name: 'get_metadata', methods: ['GET'])]
    public function getMetadata(Request $request): JsonResponse
    {
        $projectKey = $request->query->get('projectKey', 'WEB');

        try {
            $meta = $this->jiraClient->getCreateMeta($projectKey);

            return $this->json([
                'success' => true,
                'metadata' => $meta,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error fetching metadata',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Lister les tickets Jira
     */
    #[Route('/tickets', name: 'list_tickets', methods: ['GET'])]
    public function listTickets(Request $request): JsonResponse
    {
        try {
            // Récupération des paramètres de requête
            $projectKey = $request->query->get('project');
            $page = max(1, (int) $request->query->get('page', 1));
            $perPage = min(100, max(1, (int) $request->query->get('per_page', 20)));
            $jql = $request->query->get('jql');

            // Calcul de l'offset pour la pagination
            $startAt = ($page - 1) * $perPage;

            // Appel au service Jira
            $result = $this->jiraClient->listIssues(
                projectKey: $projectKey,
                jql: $jql,
                maxResults: $perPage,
                startAt: $startAt
            );

            // Enrichissement des données avec les URLs
            $issues = array_map(function($issue) {
                return [
                    'key' => $issue['key'],
                    'id' => $issue['id'],
                    'summary' => $issue['fields']['summary'] ?? 'N/A',
                    'description' => $this->extractDescriptionText($issue['fields']['description'] ?? null),
                    'status' => $issue['fields']['status']['name'] ?? 'N/A',
                    'type' => $issue['fields']['issuetype']['name'] ?? 'N/A',
                    'priority' => $issue['fields']['priority']['name'] ?? 'N/A',
                    'created' => $issue['fields']['created'] ?? null,
                    'updated' => $issue['fields']['updated'] ?? null,
                    'assignee' => $issue['fields']['assignee']['displayName'] ?? 'Non assigné',
                    'reporter' => $issue['fields']['reporter']['displayName'] ?? 'N/A',
                    'url' => $this->generateJiraUrl($issue['key'])
                ];
            }, $result['issues']);

            return $this->json([
                'success' => true,
                'data' => [
                    'issues' => $issues,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $result['total'],
                        'total_pages' => ceil($result['total'] / $perPage),
                        'start_at' => $result['startAt'],
                        'max_results' => $result['maxResults']
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des tickets', [
                'error' => $e->getMessage()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tickets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère l'URL complète du ticket Jira
     */
    private function generateJiraUrl(string $ticketKey): string
    {
        $baseUrl = $_ENV['JIRA_BASE_URL'] ?? '';
        return $baseUrl . '/browse/' . $ticketKey;
    }

    /**
     * Extrait le texte d'une description au format ADF
     */
    private function extractDescriptionText(?array $adfDescription): string
    {
        if (!$adfDescription || !isset($adfDescription['content'])) {
            return '';
        }

        $text = '';
        foreach ($adfDescription['content'] as $node) {
            if ($node['type'] === 'paragraph' && isset($node['content'])) {
                foreach ($node['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $text .= $content['text'] . "\n";
                    }
                }
            }
        }

        return trim($text);
    }
}
