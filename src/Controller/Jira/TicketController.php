<?php

namespace App\Controller\Jira;

use App\Service\JiraClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
* Contrôleur pour la gestion des tickets Jira
*/
class TicketController extends AbstractController
{
private JiraClient $jiraClient;
private LoggerInterface $logger;

public function __construct(JiraClient $jiraClient, LoggerInterface $logger)
{
$this->jiraClient = $jiraClient;
$this->logger = $logger;
}

/**
* Affiche le formulaire de création de ticket
*/
#[Route('/ticket/create', name: 'ticket_create_form', methods: ['GET'])]
public function createJiraForm(): Response
{
return $this->render('ticket/create.html.twig');
}

/**
* Traite la soumission du formulaire de création de ticket
*
* Cette méthode gère à la fois les tickets simples et ceux avec pièces jointes
*/
#[Route('/ticket/create', name: 'ticket_create', methods: ['POST'])]
public function create(Request $request, ValidatorInterface $validator): JsonResponse
{
try {
// Récupération des données du formulaire
$summary = $request->request->get('summary');
$description = $request->request->get('description');
$projectKey = $request->request->get('project_key'); // Optionnel
$issueType = $request->request->get('issue_type'); // Optionnel
$author = $request->request->get('author');
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

// Vérification de la présence d'une pièce jointe
$uploadedFile = $request->files->get('attachment');

if ($uploadedFile && $uploadedFile->isValid()) {
// Création du ticket avec pièce jointe
$this->logger->info('Création d\'un ticket avec pièce jointe', [
'summary' => $summary,
'filename' => $uploadedFile->getClientOriginalName()
]);

$result = $this->jiraClient->createIssueWithAttachment(
$summary,
$description,
$uploadedFile,
$projectKey,
$issueType,
["customfield_10114" => $author]
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

} else {
// Création du ticket simple sans pièce jointe
$this->logger->info('Création d\'un ticket simple', [
'summary' => $summary
]);

$result = $this->jiraClient->createIssue(
$summary,
$description,
$projectKey,
$issueType,
    null,
    ["customfield_10114" => $author]
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
* Test de la connexion Jira
*/
#[Route('/ticket/test-connection', name: 'ticket_test_connection', methods: ['GET'])]
public function testConnection(): JsonResponse
{
$isConnected = $this->jiraClient->testConnection();

return $this->json([
'connected' => $isConnected,
'message' => $isConnected ? 'Connexion Jira OK' : 'Échec de connexion Jira'
]);
}

/**
* Génère l'URL complète du ticket Jira
*/
private function generateJiraUrl(string $ticketKey): string
{
$baseUrl = $_ENV['JIRA_BASE_URL'] ?? '';
return $baseUrl . '/browse/' . $ticketKey;
}
}
