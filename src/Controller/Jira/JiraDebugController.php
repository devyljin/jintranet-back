<?php
// Contrôleur de debug pour tester votre configuration Jira
// src/Controller/JiraDebugController.php

namespace App\Controller\Jira;

use App\Service\JiraClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour déboguer et tester la configuration Jira
 */
#[Route('/api/v1/debug/jira', name: 'jira_debug_connection')]
class JiraDebugController extends AbstractController
{
    private JiraClient $jiraClient;

    public function __construct(JiraClient $jiraClient)
    {
        $this->jiraClient = $jiraClient;
    }

    /**
     * Test de base de la connexion
     */
    #[Route('/connection', name: 'jira_debug_connection')]
    public function testConnection(): JsonResponse
    {
        try {
            $isConnected = $this->jiraClient->testConnection();

            return $this->json([
                'success' => $isConnected,
                'message' => $isConnected ? 'Connexion Jira OK' : 'Échec de connexion',
                'timestamp' => new \DateTime()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupère les métadonnées de création pour le projet WEB
     */
    #[Route('/createmeta', name: 'jira_debug_createmeta')]
    public function getCreateMeta(): JsonResponse
    {
        try {
            $meta = $this->jiraClient->getCreateMeta('WEB');

            return $this->json([
                'success' => true,
                'data' => $meta,
                'message' => 'Métadonnées récupérées avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération des métadonnées'
            ], 500);
        }
    }

    /**
     * Test de création d'un ticket minimal
     */
    #[Route('/create-test', name: 'jira_debug_create_test')]
    public function createTestTicket(): JsonResponse
    {
        try {
            $result = $this->jiraClient->createIssue(
                'Test ticket - ' . date('Y-m-d H:i:s'),
                'Ticket de test créé automatiquement pour valider la configuration.',
                'WEB', // Votre projet
                'Task' // Sera converti en "Tâche"
            );

            return $this->json([
                'success' => true,
                'ticket' => [
                    'key' => $result['key'],
                    'id' => $result['id'],
                    'url' => 'https://agrume.atlassian.net/browse/' . $result['key']
                ],
                'message' => 'Ticket de test créé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la création du ticket de test'
            ], 500);
        }
    }

    /**
     * Récupère un ticket existant pour vérifier le format
     */
    #[Route('/get/{ticketKey}', name: 'jira_debug_get_ticket')]
    public function getTicket(string $ticketKey): JsonResponse
    {
        try {
            $ticket = $this->jiraClient->getIssue($ticketKey);

            return $this->json([
                'success' => true,
                'ticket' => $ticket,
                'message' => 'Ticket récupéré avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération du ticket'
            ], 500);
        }
    }

    /**
     * Affiche toutes les informations de debug en une page
     */
    #[Route('/debug', name: 'jira_debug_dashboard')]
    public function debugDashboard(): JsonResponse
    {
        $results = [];

        // Test de connexion
        try {
            $results['connection'] = [
                'success' => $this->jiraClient->testConnection(),
                'message' => 'Test de connexion effectué'
            ];
        } catch (\Exception $e) {
            $results['connection'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        // Récupération des métadonnées
        try {
            $meta = $this->jiraClient->getCreateMeta('WEB');
            $results['metadata'] = [
                'success' => true,
                'project_name' => $meta['projects'][0]['name'] ?? 'N/A',
                'available_issue_types' => array_map(function($type) {
                    return $type['name'];
                }, $meta['projects'][0]['issuetypes'] ?? [])
            ];
        } catch (\Exception $e) {
            $results['metadata'] = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }

        return $this->json([
            'timestamp' => new \DateTime(),
            'configuration' => [
                'base_url' => $_ENV['JIRA_BASE_URL'] ?? 'Non configuré',
                'project_key' => $_ENV['JIRA_DEFAULT_PROJECT_KEY'] ?? 'Non configuré',
                'issue_type' => $_ENV['JIRA_DEFAULT_ISSUE_TYPE'] ?? 'Non configuré'
            ],
            'tests' => $results
        ]);
    }
}
