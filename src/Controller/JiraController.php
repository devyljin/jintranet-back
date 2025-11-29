<?php

namespace App\Controller;

use App\Service\JiraClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/jira', name: 'api_jira_')]
class JiraController extends AbstractController
{
    private JiraClient $jiraClient;

    public function __construct(JiraClient $jiraClient)
    {
        $this->jiraClient = $jiraClient;
    }

    /**
     * Créer un nouveau ticket Jira
     */
    #[Route('/tickets', name: 'create_ticket', methods: ['POST'])]
    public function createTicket(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['summary']) || !isset($data['description'])) {
            return $this->json([
                'success' => false,
                'message' => 'Summary and description are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->jiraClient->createIssue(
                $data['summary'],
                $data['description'],
                $data['projectKey'] ?? null,
                $data['issueType'] ?? null,
                $data['assigneeAccountId'] ?? null,
                $data['additionalFields'] ?? []
            );

            return $this->json([
                'success' => true,
                'ticket' => [
                    'key' => $result['key'],
                    'id' => $result['id'],
                    'self' => $result['self'],
                ],
                'message' => 'Ticket created successfully',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error creating ticket',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
