<?php

namespace Database\Seeders;

use App\Models\PhishingAttack;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PhishingAttackSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('domain', 'example.com')->first();
        if (! $tenant) {
            return;
        }
        $this->seedAttacksForTenant($tenant);
    }

    /**
     * Seed default attack templates for a tenant. Call statically from commands.
     */
    public static function seedForTenant(Tenant $tenant): void
    {
        (new self())->seedAttacksForTenant($tenant);
    }

    /**
     * Seed default attack templates for a tenant (e.g. when creating a new tenant).
     */
    public function seedAttacksForTenant(Tenant $tenant): void
    {
        $attacks = self::defaultAttacks();

        foreach ($attacks as $a) {
            PhishingAttack::withoutGlobalScope('tenant')->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $a['name'],
                ],
                array_merge($a, [
                    'tenant_id' => $tenant->id,
                    'times_sent' => 0,
                    'times_clicked' => 0,
                    'active' => true,
                ])
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function defaultAttacks(): array
    {
        return [
            // Difficulty 1 – Obvious
            [
                'name' => 'Obvious typo account suspension',
                'description' => 'Fake account suspension with deliberate misspellings and generic greeting. Easy to spot.',
                'subject' => 'Your acount has been supsended - Act now',
                'from_name' => 'Support Team',
                'from_email' => 'support@secure-login.com',
                'html_body' => '<p>Dear User,</p><p>Your acount will be permantly closed unless you verify within 24 hour. <a href="#">Click here to verify</a>.</p><p>Thank you, Support Team</p>',
                'text_body' => "Dear User,\nYour acount will be permantly closed unless you verify within 24 hour. Click here to verify.\nThank you, Support Team",
                'difficulty_rating' => 1,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Generic prize winner',
                'description' => 'Classic "you have won" scam with poor grammar and no legitimate sender.',
                'subject' => 'CONGRATULATION!! You have WON $$$',
                'from_name' => 'Prize Department',
                'from_email' => 'winner@promo-mail.net',
                'html_body' => '<p>Dear Winner,</p><p>You have been select to recieve a large prize. Send your bank details to claim.</p><p><a href="#">Claim Now</a></p>',
                'text_body' => "Dear Winner,\nYou have been select to recieve a large prize. Send your bank details to claim.\nClaim Now",
                'difficulty_rating' => 1,
                'landing_page_type' => 'training',
            ],
            // Difficulty 2 – Easy to spot
            [
                'name' => 'Google account deactivation (obvious)',
                'description' => 'Google-style deactivation threat but with wrong domain and awkward wording.',
                'subject' => 'Google Account - Action Required',
                'from_name' => 'Google Account',
                'from_email' => 'noreply@google-account-security.com',
                'html_body' => '<p>Hello,</p><p>We noticed unusual activity. Your Google account might be deactivated. Please verify your identity by clicking the link below.</p><p><a href="#">Verify my account</a></p><p>Google Team</p>',
                'text_body' => "Hello,\nWe noticed unusual activity. Your Google account might be deactivated. Please verify your identity by clicking the link below.\nVerify my account\nGoogle Team",
                'difficulty_rating' => 2,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Password expiry notice (easy)',
                'description' => 'IT-style password expiry with slightly suspicious sender and urgency.',
                'subject' => 'Your password expires in 24 hours',
                'from_name' => 'IT Support',
                'from_email' => 'it@company-support.org',
                'html_body' => '<p>Your password will expire soon. To keep your account active, update it now.</p><p><a href="#">Update password</a></p><p>This is an automated message.</p>',
                'text_body' => "Your password will expire soon. To keep your account active, update it now.\nUpdate password\nThis is an automated message.",
                'difficulty_rating' => 2,
                'landing_page_type' => 'training',
            ],
            // Difficulty 3 – Moderate
            [
                'name' => 'Google Photos storage full',
                'description' => 'Mimics Google Photos storage warning; moderate realism with plausible wording.',
                'subject' => 'Your Google Photos storage is full',
                'from_name' => 'Google Photos',
                'from_email' => 'photos-noreply@google.com',
                'html_body' => '<p>Hi there,</p><p>Your Google Photos storage has reached its limit. You may lose access to existing photos unless you free up space or get more storage.</p><p><a href="#">Manage storage</a></p><p>– The Google Photos team</p>',
                'text_body' => "Hi there,\nYour Google Photos storage has reached its limit. You may lose access to existing photos unless you free up space or get more storage.\nManage storage\n– The Google Photos team",
                'difficulty_rating' => 3,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Unusual sign-in attempt',
                'description' => 'Fake "unusual sign-in" alert; moderate polish with familiar phrasing.',
                'subject' => 'Unusual sign-in attempt - Google',
                'from_name' => 'Google',
                'from_email' => 'no-reply@accounts.google.com',
                'html_body' => '<p>Someone just tried to sign in to your Google Account. If this was you, you can ignore this email. If not, we recommend you secure your account.</p><p><a href="#">Review activity</a></p><p>Google Account team</p>',
                'text_body' => "Someone just tried to sign in to your Google Account. If this was you, you can ignore this email. If not, we recommend you secure your account.\nReview activity\nGoogle Account team",
                'difficulty_rating' => 3,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Document shared with you',
                'description' => 'Fake "someone shared a document" to lure clicks; common phish pattern.',
                'subject' => 'John shared a document with you',
                'from_name' => 'Google Drive',
                'from_email' => 'drive-noreply@google.com',
                'html_body' => '<p>A document has been shared with you. Open it to view or collaborate.</p><p><a href="#">Open document</a></p><p>Google Drive</p>',
                'text_body' => "A document has been shared with you. Open it to view or collaborate.\nOpen document\nGoogle Drive",
                'difficulty_rating' => 3,
                'landing_page_type' => 'training',
            ],
            // Difficulty 4 – Convincing
            [
                'name' => 'Duo push notification failed',
                'description' => 'Mimics Duo Security "approve sign-in" / backup code request; high realism.',
                'subject' => 'Duo Security: Approve sign-in request',
                'from_name' => 'Duo Security',
                'from_email' => 'duo@duosecurity.com',
                'html_body' => '<p>You have a pending sign-in request. We were unable to send a push to your device. Use the link below to approve or deny this request.</p><p><a href="#">Review sign-in request</a></p><p>If you did not request this, deny the request and change your password.</p><p>Duo Security</p>',
                'text_body' => "You have a pending sign-in request. We were unable to send a push to your device. Use the link below to approve or deny this request.\nReview sign-in request\nIf you did not request this, deny the request and change your password.\nDuo Security",
                'difficulty_rating' => 4,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Google account will be limited',
                'description' => 'Account limitation warning with policy-style language; convincing tone.',
                'subject' => 'Action required: Your Google Account may be limited',
                'from_name' => 'Google Accounts',
                'from_email' => 'accounts-noreply@google.com',
                'html_body' => '<p>We need to verify that this account belongs to you. To avoid your account being limited, please confirm your identity within 48 hours.</p><p><a href="#">Verify identity</a></p><p>Google Accounts team</p>',
                'text_body' => "We need to verify that this account belongs to you. To avoid your account being limited, please confirm your identity within 48 hours.\nVerify identity\nGoogle Accounts team",
                'difficulty_rating' => 4,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Microsoft 365 sign-in from new location',
                'description' => 'Mimics Microsoft "sign-in from new location" security email.',
                'subject' => 'Microsoft 365 – sign-in from new location',
                'from_name' => 'Microsoft account team',
                'from_email' => 'account-security-noreply@accountprotection.microsoft.com',
                'html_body' => '<p>We detected a sign-in to your Microsoft account from a new location. If this was you, no action is needed. If not, secure your account now.</p><p><a href="#">Secure account</a></p><p>Microsoft account team</p>',
                'text_body' => "We detected a sign-in to your Microsoft account from a new location. If this was you, no action is needed. If not, secure your account now.\nSecure account\nMicrosoft account team",
                'difficulty_rating' => 4,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Google Photos will be removed',
                'description' => 'Threat that photos will be removed unless identity is verified; high pressure.',
                'subject' => 'Verify your identity – Google Photos',
                'from_name' => 'Google Photos',
                'from_email' => 'noreply@photos.google.com',
                'html_body' => '<p>We could not verify that this account is yours. Unless you confirm your identity within 7 days, your Google Photos content may be removed from our systems.</p><p><a href="#">Verify now</a></p><p>Google Photos</p>',
                'text_body' => "We could not verify that this account is yours. Unless you confirm your identity within 7 days, your Google Photos content may be removed from our systems.\nVerify now\nGoogle Photos",
                'difficulty_rating' => 4,
                'landing_page_type' => 'training',
            ],
            // Difficulty 5 – Very realistic
            [
                'name' => 'Invalid login attempt – Google',
                'description' => 'Polished "invalid password" / sign-in attempt alert; very realistic wording and branding style.',
                'subject' => 'Invalid sign-in attempt to your Google Account',
                'from_name' => 'Google',
                'from_email' => 'no-reply@accounts.google.com',
                'html_body' => '<p>We noticed a sign-in attempt to your Google Account from a device we don\'t recognize. The sign-in was blocked because the password was incorrect.</p><p>If this was you, you can try again or reset your password. If not, we recommend you review your account activity and change your password.</p><p><a href="#">Review activity and security</a></p><p>Google Account team</p>',
                'text_body' => "We noticed a sign-in attempt to your Google Account from a device we don't recognize. The sign-in was blocked because the password was incorrect.\nIf this was you, you can try again or reset your password. If not, we recommend you review your account activity and change your password.\nReview activity and security\nGoogle Account team",
                'difficulty_rating' => 5,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Scheduled deletion of your Google Account',
                'description' => 'Account deletion warning with policy-style language; very convincing.',
                'subject' => 'Your Google Account is scheduled for deletion',
                'from_name' => 'Google Accounts',
                'from_email' => 'accounts-noreply@google.com',
                'html_body' => '<p>Your Google Account is scheduled to be deleted in 30 days due to a policy concern. If you believe this is a mistake, you can request a review by verifying your identity.</p><p><a href="#">Request review</a></p><p>Google Accounts</p>',
                'text_body' => "Your Google Account is scheduled to be deleted in 30 days due to a policy concern. If you believe this is a mistake, you can request a review by verifying your identity.\nRequest review\nGoogle Accounts",
                'difficulty_rating' => 5,
                'landing_page_type' => 'training',
            ],
            [
                'name' => 'Duo Security – approve login',
                'description' => 'Duo "approve this login" push-style email; very realistic.',
                'subject' => 'Duo: Approve this login',
                'from_name' => 'Duo Security',
                'from_email' => 'duo@duosecurity.com',
                'html_body' => '<p>A login attempt is waiting for your approval. Tap Approve on your Duo Mobile app, or use the link below to approve or deny.</p><p><a href="#">Approve or deny</a></p><p>Request will expire in 10 minutes. If you didn\'t request this, deny and change your password.</p><p>– Duo Security</p>',
                'text_body' => "A login attempt is waiting for your approval. Tap Approve on your Duo Mobile app, or use the link below to approve or deny.\nApprove or deny\nRequest will expire in 10 minutes. If you didn't request this, deny and change your password.\n– Duo Security",
                'difficulty_rating' => 5,
                'landing_page_type' => 'training',
            ],
        ];
    }
}
