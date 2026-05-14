<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailTemplate::with('emailTemplateLangs');

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('from', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $allowedSorts=['name', 'created_at'];
        $allowedDirection = ['asc', 'desc'];
        if (!in_array($sortDirection, $allowedDirection)) {
            $sortDirection = 'desc';
        }
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $templates = $query->paginate((int)$perPage);

        return Inertia::render('email-templates/index', [
            'templates' => $templates,
            'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page'])
        ]);
    }

    public function show(EmailTemplate $emailTemplate)
    {
        $template = $emailTemplate->load('emailTemplateLangs');
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);

        // Template-specific variables
        $variables = [];

        if ($template->name === 'User Created') {
            $variables = [
                '{app_url}' => 'App URL',
                '{user_name}' => 'User Name',
                '{user_email}' => 'User Email',
                '{user_password}' => 'User Password',
                '{user_type}' => 'User Type',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Lead Assigned') {
            $variables = [
                '{lead_name}' => 'Lead Name',
                '{assigned_user_name}' => 'Assign User',
                '{lead_email}' => 'Lead Email',
                '{lead_phone}' => 'Lead Phone',
                '{lead_company}' => 'Lead Company',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Lead Moved') {
            $variables = [
                '{lead_name}' => 'Lead Name',
                '{assigned_user_name}' => 'Assign User',
                '{old_lead_stage}' => 'Old Status',
                '{new_lead_stage}' => 'New Status',
                '{lead_email}' => 'Lead Email',
                '{lead_phone}' => 'Lead Phone',
                '{lead_company}' => 'Lead Company',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Quote Created') {
            $variables = [
                '{quote_number}' => 'Quote Number',
                '{quote_name}' => 'Quote Name',
                '{billing_contact_name}' => 'Billing Contact Name',
                '{account_name}' => 'Account Name',
                '{quote_total}' => 'Quote Total Amount',
                '{quote_valid_until}' => 'Quote Valid Until Date',
                '{quote_status}' => 'Quote Status',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Quote Status Changed') {
            $variables = [
                '{quote_number}' => 'Quote Number',
                '{quote_name}' => 'Quote Name',
                '{billing_contact_name}' => 'Billing Contact Name',
                '{account_name}' => 'Account Name',
                '{quote_total}' => 'Quote Total Amount',
                '{quote_valid_until}' => 'Quote Valid Until Date',
                '{old_quote_status}' => 'Old Quote Status',
                '{new_quote_status}' => 'New Quote Status',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Task Assigned') {
            $variables = [
                '{task_title}' => 'Task Title',
                '{assigned_user_name}' => 'Assigned User Name',
                '{project_name}' => 'Project Name',
                '{task_priority}' => 'Task Priority',
                '{task_due_date}' => 'Task Due Date',
                '{task_status}' => 'Task Status',
                '{task_estimated_hours}' => 'Task Estimated Hours',
                '{task_description}' => 'Task Description',
                '{creator_name}' => 'Creator Name',
                '{creator_email}' => 'Creator Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Meeting Invitation') {
            $variables = [
                '{meeting_title}' => 'Meeting Title',
                '{attendee_name}' => 'Attendee Name',
                '{meeting_date}' => 'Meeting Date',
                '{meeting_start_time}' => 'Meeting Start Time',
                '{meeting_end_time}' => 'Meeting End Time',
                '{meeting_location}' => 'Meeting Location',
                '{meeting_description}' => 'Meeting Description',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Case Created') {
            $variables = [
                '{contact_name}' => 'Contact Name',
                '{case_subject}' => 'Case Subject',
                '{case_priority}' => 'Case Priority',
                '{case_status}' => 'Case Status',
                '{case_created_date}' => 'Case Created Date',
                '{case_description}' => 'Case Description',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Opportunity Created') {
            $variables = [
                '{opportunity_name}' => 'Opportunity Name',
                '{account_name}' => 'Account Name',
                '{contact_name}' => 'Contact Name',
                '{opportunity_stage}' => 'Opportunity Stage',
                '{opportunity_amount}' => 'Opportunity Amount',
                '{opportunity_close_date}' => 'Opportunity Close Date',
                '{opportunity_description}' => 'Opportunity Description',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Opportunity Status Changed') {
            $variables = [
                '{opportunity_name}' => 'Opportunity Name',
                '{old_opportunity_stage}' => 'Old Opportunity Stage',
                '{new_opportunity_stage}' => 'New Opportunity Stage',
                '{account_name}' => 'Account Name',
                '{contact_name}' => 'Contact Name',
                '{opportunity_amount}' => 'Opportunity Amount',
                '{opportunity_close_date}' => 'Opportunity Close Date',
                '{opportunity_description}' => 'Opportunity Description',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Invoice Payment Reminder') {
            $variables = [
                '{billing_contact_name}' => 'Billing Contact Name',
                '{invoice_number}' => 'Invoice Number',
                '{invoice_date}' => 'Invoice Date',
                '{invoice_due_date}' => 'Invoice Due Date',
                '{invoice_total}' => 'Invoice Total Amount',
                '{invoice_amount_due}' => 'Invoice Amount Due',
                '{invoice_payment_link}' => 'Invoice Payment Link',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Invoice Created') {
            $variables = [
                '{invoice_number}' => 'Invoice Number',
                '{invoice_name}' => 'Invoice Name',
                '{contact_name}' => 'Contact Name',
                '{account_name}' => 'Account Name',
                '{invoice_total}' => 'Invoice Total Amount',
                '{invoice_date}' => 'Invoice Date',
                '{due_date}' => 'Due Date',
                '{invoice_status}' => 'Invoice Status',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Purchase Order Created') {
            $variables = [
                '{purchase_order_number}' => 'Purchase Order Number',
                '{purchase_order_name}' => 'Purchase Order Name',
                '{contact_name}' => 'Contact Name',
                '{account_name}' => 'Account Name',
                '{purchase_order_total}' => 'Purchase Order Total Amount',
                '{purchase_order_date}' => 'Purchase Order Date',
                '{expected_delivery_date}' => 'Expected Delivery Date',
                '{purchase_order_status}' => 'Purchase Order Status',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Receipt Order Created') {
            $variables = [
                '{receipt_number}' => 'Receipt Number',
                '{receipt_name}' => 'Receipt Name',
                '{contact_name}' => 'Contact Name',
                '{account_name}' => 'Account Name',
                '{receipt_total}' => 'Receipt Total Amount',
                '{receipt_date}' => 'Receipt Date',
                '{expected_receipt_date}' => 'Expected Receipt Date',
                '{receipt_status}' => 'Receipt Status',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Return Order Created') {
            $variables = [
                '{return_number}' => 'Return Number',
                '{return_name}' => 'Return Name',
                '{contact_name}' => 'Contact Name',
                '{account_name}' => 'Account Name',
                '{return_total}' => 'Return Total Amount',
                '{return_date}' => 'Return Date',
                '{return_status}' => 'Return Status',
                '{return_reason}' => 'Return Reason',
                '{tracking_number}' => 'Tracking Number',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Sales Order Created') {
            $variables = [
                '{order_number}' => 'Order Number',
                '{order_name}' => 'Order Name',
                '{billing_contact_name}' => 'Billing Contact Name',
                '{account_name}' => 'Account Name',
                '{order_total}' => 'Order Total Amount',
                '{order_date}' => 'Order Date',
                '{delivery_date}' => 'Delivery Date',
                '{order_status}' => 'Order Status',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        } elseif ($template->name === 'Delivery Order Created') {
            $variables = [
                '{delivery_order_number}' => 'Delivery Order Number',
                '{delivery_order_name}' => 'Delivery Order Name',
                '{contact_name}' => 'Contact Name',
                '{account_name}' => 'Account Name',
                '{delivery_date}' => 'Delivery Date',
                '{expected_delivery_date}' => 'Expected Delivery Date',
                '{delivery_status}' => 'Delivery Status',
                '{tracking_number}' => 'Tracking Number',
                '{assigned_user_name}' => 'Assigned User Name',
                '{assigned_user_email}' => 'Assigned User Email',
                '{company_name}' => 'Company Name'
            ];
        }

        return Inertia::render('email-templates/show', [
            'template' => $template,
            'languages' => $languages,
            'variables' => $variables
        ]);
    }

    public function updateSettings(EmailTemplate $emailTemplate, Request $request)
    {
        try {
            $request->validate([
                'from' => 'required|string|max:255'
            ]);

            $emailTemplate->update([
                'from' => $request->from
            ]);

            return redirect()->back()->with('success', __('Template settings updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update template settings: :error', ['error' => $e->getMessage()]));
        }
    }

    public function updateContent(EmailTemplate $emailTemplate, Request $request)
    {
        try {
            $request->validate([
                'lang' => 'required|string|max:10',
                'subject' => 'required|string|max:255',
                'content' => 'required|string'
            ]);

            $emailTemplate->emailTemplateLangs()->updateOrCreate(
                ['lang' => $request->lang],
                [
                    'subject' => $request->subject,
                    'content' => $request->content,
                ]
            );

            return redirect()->back()->with('success', __('Email content updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update email content: :error', ['error' => $e->getMessage()]));
        }
    }
}
