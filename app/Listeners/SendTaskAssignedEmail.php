<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Models\User;
use App\Services\EmailTemplateService;
use Exception;

class SendTaskAssignedEmail
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private EmailTemplateService $emailService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TaskAssigned $event): void
    {
        $task = $event->task;
        $assignedUser = $task->assignedUser;
        $project_name = $task->project->name;
        $status = $task->taskStatus->name;
        $creator = $task->creator;

        if (isEmailTemplateEnabled('Task Assigned', createdBy()) && $assignedUser) {
            // Prepare email variables
            $variables = [
                '{task_title}' => $task->title ?? '-',
                '{assigned_user_name}' => $assignedUser->name ?? '-',
                '{project_name}' => $project_name ?? '-',
                '{task_priority}' => $task->priority ?? '-',
                '{task_due_date}' => $task->due_date ?? '-',
                '{task_status}' => $status ?? '-',
                '{task_estimated_hours}' => $task->estimated_hours ?? '-',
                '{task_description}' => $task->description,
                '{creator_name}' => $creator->name ?? '-',
                '{creator_email}' => $creator->email ?? '-',
                '{company_name}' => getCompanyName(),
            ];

            try {
            // Send email to billing contact if exists
            if ($assignedUser && $assignedUser->email) {
                $createdByUser = User::find(createdBy());
                $userLanguage = $createdByUser->lang ?? 'en';
                $this->emailService->sendTemplateEmailWithLanguage(
                    templateName: 'Task Assigned',
                    variables: $variables,
                    toEmail: $assignedUser->email,
                    toName: $assignedUser->name,
                    language: $userLanguage
                );
            }
        } catch (Exception $e) {
                // Store error in session for frontend notification
                session()->flash('email_error', 'Failed to send Task Assigned email: ' . $e->getMessage());
            }
        }
    }
}
