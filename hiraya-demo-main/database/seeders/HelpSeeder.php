<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class HelpSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Account and Access', 'slug' => 'account-and-access', 'sort_order' => 1],
            ['name' => 'Enrollment', 'slug' => 'enrollment', 'sort_order' => 2],
            ['name' => 'Grades', 'slug' => 'grades', 'sort_order' => 3],
            ['name' => 'Payments', 'slug' => 'payments', 'sort_order' => 4],
            ['name' => 'General', 'slug' => 'general', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            FaqCategory::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }

        $faqs = [
            [
                'category' => 'account-and-access',
                'question' => 'How do I reset my password?',
                'answer' => 'On the sign-in page, choose “Forgot password?”, enter the email address attached to your account, and submit the form. A six-digit verification code will be sent to that email address. Enter the code, then choose a new password. If you do not receive a code, contact the administrator or registrar.',
                'role' => null,
            ],
            [
                'category' => 'account-and-access',
                'question' => 'Why is my account locked?',
                'answer' => 'After three failed sign-in attempts, the account is locked for five minutes. Wait for the lockout period to expire, then try again. If you still cannot sign in, use the forgot-password flow or contact the administrator.',
                'role' => null,
            ],
            [
                'category' => 'account-and-access',
                'question' => 'How do I activate my account?',
                'answer' => 'Open the activation link sent to your registered email address. Choose a password that contains at least eight characters with letters and numbers. If the link has expired, ask the administrator to send a new invitation.',
                'role' => null,
            ],
            [
                'category' => 'enrollment',
                'question' => 'When can I enroll?',
                'answer' => 'The enrollment window is displayed on your student dashboard. Enrollment is only accepted while the active academic year is inside its enrollment period. If the window is closed, wait for the next announced period or contact the registrar.',
                'role' => 'student',
            ],
            [
                'category' => 'enrollment',
                'question' => 'Why can’t I select two classes?',
                'answer' => 'The system prevents schedule overlaps. If the days and times of two classes overlap, remove one of them before submitting your enrollment. You can check the offered class schedule on the enrollment page.',
                'role' => 'student',
            ],
            [
                'category' => 'enrollment',
                'question' => 'What happens after I submit my enrollment?',
                'answer' => 'Your class selections are saved and your assessment is added to your student ledger. Your class schedule and class roster become available on your dashboard. Contact the registrar if you need to change a submitted enrollment.',
                'role' => 'student',
            ],
            [
                'category' => 'grades',
                'question' => 'How is my final rating computed?',
                'answer' => 'The final rating is the weighted average of the midterm and final ratings. Both components are currently weighted at fifty percent. A final rating of at least 75 is marked as passed.',
                'role' => 'student',
            ],
            [
                'category' => 'grades',
                'question' => 'Why is a grade not showing yet?',
                'answer' => 'Grades appear after the assigned faculty member has encoded and submitted them. If other subjects already show grades, your faculty may still be completing the remaining class records.',
                'role' => 'student',
            ],
            [
                'category' => 'payments',
                'question' => 'How do I pay my balance?',
                'answer' => 'Open the Payments page on your student dashboard, enter the amount and payment method, then provide the reference number and a clear photo or PDF of your receipt. Finance will verify the submission. Your balance is updated only after verification.',
                'role' => 'student',
            ],
            [
                'category' => 'payments',
                'question' => 'Why was my payment rejected?',
                'answer' => 'Finance may reject a payment when the reference number cannot be verified, the receipt is unreadable, or the amount does not match the submitted record. Open the payment history to read the remarks, then submit a corrected payment with a clearer proof of payment.',
                'role' => 'student',
            ],
            [
                'category' => 'payments',
                'question' => 'How do I post a walk-in payment?',
                'answer' => 'Finance staff can open the payment posting page, search for the student, review the outstanding balance, and enter the amount, method, and reference number. Verified payments are posted to the student ledger and a receipt number is generated automatically.',
                'role' => 'finance',
            ],
            [
                'category' => 'general',
                'question' => 'Who do I contact for account concerns?',
                'answer' => 'For account activation, role changes, or missing student records, contact the registrar or the system administrator. For payment concerns, contact the finance office. Include your student number and registered email address in your message.',
                'role' => null,
            ],
        ];

        foreach ($faqs as $index => $faq) {
            $category = FaqCategory::query()->where('slug', $faq['category'])->firstOrFail();

            Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                [
                    'faq_category_id' => $category->id,
                    'answer' => $faq['answer'],
                    'role_visibility' => $faq['role'],
                    'sort_order' => $index + 1,
                    'is_published' => true,
                ],
            );
        }
    }
}
