<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $templates = $this->getPredefinedTemplates();
        
        foreach ($templates as $template) {
            DB::table('awareness_email_templates')->insert([
                'subject' => $template['subject'],
                'body' => $template['body'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('awareness_email_templates')->truncate();
    }

    /**
     * Get predefined security awareness templates.
     */
    private function getPredefinedTemplates()
    {
        return [
            [
                'subject' => 'Mobile Device Security Best Practices',
                'body' => '<p>Dear {{user_name}},</p>

<p>Mobile devices are an essential part of our work life. Here are some simple ways to keep your mobile devices secure while staying productive.</p>

<p><strong>Essential Mobile Security Tips:</strong></p>
<ul>
    <li>Always use a PIN, password, or biometric lock on your device</li>
    <li>Keep your device\'s operating system and apps updated</li>
    <li>Only download apps from official app stores</li>
    <li>Avoid connecting to unsecured public Wi-Fi networks</li>
    <li>Enable remote wipe capabilities in case of loss or theft</li>
    <li>Back up your data regularly</li>
</ul>

<p>If you lose your work device, please report it to IT immediately so we can help secure your accounts.</p>

<p>Stay secure,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Social Engineering: What You Need to Know',
                'body' => '<p>Hi {{user_name}},</p>

<p>Social engineering is when someone tries to manipulate you into sharing confidential information or performing certain actions. Let\'s learn how to recognize these attempts.</p>

<p><strong>Common Social Engineering Tactics:</strong></p>
<ul>
    <li><strong>Pretexting:</strong> Creating a fabricated scenario to obtain information</li>
    <li><strong>Baiting:</strong> Offering something enticing to trick you into a trap</li>
    <li><strong>Tailgating:</strong> Following authorized personnel into restricted areas</li>
    <li><strong>Quid Pro Quo:</strong> Offering a service in exchange for information</li>
</ul>

<p><strong>How to Protect Yourself:</strong><br>
Always verify identities before sharing information, follow security protocols strictly, and report suspicious interactions to the security team.</p>

<p>Your awareness is our strongest defense!</p>

<p>Best regards,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Secure File Sharing Guidelines',
                'body' => '<p>Hello {{user_name}},</p>

<p>Sharing files securely is crucial for protecting sensitive information. Here are our recommended practices for safe file sharing.</p>

<p><strong>Best Practices for File Sharing:</strong></p>
<ol>
    <li>Use approved company tools for sharing files (avoid personal cloud services)</li>
    <li>Encrypt sensitive files before sharing</li>
    <li>Set expiration dates on shared links when possible</li>
    <li>Verify recipient email addresses before sending</li>
    <li>Use password protection for sensitive documents</li>
    <li>Avoid sending sensitive data via email when possible</li>
</ol>

<p>Need help with secure file sharing? Contact IT and we\'ll guide you through the process.</p>

<p>Thank you for keeping our data safe,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Understanding Ransomware and How to Avoid It',
                'body' => '<p>Dear {{user_name}},</p>

<p>Ransomware is malicious software that locks your files and demands payment. While this sounds scary, you can easily protect yourself with awareness and good habits.</p>

<p><strong>How Ransomware Spreads:</strong></p>
<ul>
    <li>Malicious email attachments</li>
    <li>Infected websites and downloads</li>
    <li>Compromised software or apps</li>
    <li>Unpatched system vulnerabilities</li>
</ul>

<p><strong>Protection Steps:</strong></p>
<ul>
    <li>Don\'t open attachments from unknown senders</li>
    <li>Keep all software updated</li>
    <li>Back up important files regularly</li>
    <li>Use antivirus software</li>
    <li>Report suspicious emails immediately</li>
</ul>

<p>Remember: We will never ask you to pay a ransom. Contact IT immediately if you suspect ransomware.</p>

<p>Stay protected,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Wi-Fi Security: Connecting Safely',
                'body' => '<p>Hi {{user_name}},</p>

<p>Wi-Fi connectivity is convenient, but it\'s important to connect safely. Here\'s what you need to know about secure wireless connections.</p>

<p><strong>Public Wi-Fi Risks:</strong><br>
Public Wi-Fi networks are often unsecured, making it easy for attackers to intercept your data. Avoid accessing sensitive information on public networks.</p>

<p><strong>Safe Wi-Fi Practices:</strong></p>
<ul>
    <li>Use your mobile hotspot instead of public Wi-Fi when possible</li>
    <li>Connect to VPN before accessing company resources</li>
    <li>Verify the network name with staff before connecting</li>
    <li>Disable auto-connect features on your devices</li>
    <li>Turn off file sharing when on public networks</li>
</ul>

<p>When in doubt, use your cellular data for sensitive work.</p>

<p>Stay connected safely,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Physical Security Matters Too',
                'body' => '<p>Hello {{user_name}},</p>

<p>Cybersecurity isn\'t just about digital threats. Physical security plays a crucial role in protecting our information and assets.</p>

<p><strong>Physical Security Reminders:</strong></p>
<ul>
    <li>Always wear your ID badge visibly</li>
    <li>Don\'t hold doors open for people you don\'t recognize</li>
    <li>Report unescorted visitors or suspicious individuals</li>
    <li>Keep sensitive documents secured when not in use</li>
    <li>Clear your desk of confidential materials at day\'s end</li>
    <li>Secure laptops and mobile devices, even at your desk</li>
</ul>

<p>Physical security is everyone\'s responsibility. Thank you for doing your part!</p>

<p>Best regards,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Software Updates: Why They Matter',
                'body' => '<p>Dear {{user_name}},</p>

<p>We know software update notifications can be inconvenient, but they\'re one of the most important security measures we have. Here\'s why they matter.</p>

<p><strong>Why Update?</strong></p>
<ul>
    <li><strong>Security Patches:</strong> Updates fix vulnerabilities that hackers could exploit</li>
    <li><strong>Bug Fixes:</strong> They resolve issues that could cause crashes or data loss</li>
    <li><strong>New Features:</strong> Updates often include improvements and new capabilities</li>
    <li><strong>Compatibility:</strong> Keeping software current ensures everything works together</li>
</ul>

<p><strong>Best Practices:</strong><br>
Install updates promptly when notified. If you need to postpone, schedule the update for the end of your workday. Never ignore security updates.</p>

<p>Thank you for keeping your system secure!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Email Attachment Safety Guide',
                'body' => '<p>Hi {{user_name}},</p>

<p>Email attachments are a common way for malware to spread. Let\'s review how to handle attachments safely.</p>

<p><strong>Before Opening Any Attachment:</strong></p>
<ol>
    <li>Verify you were expecting the attachment</li>
    <li>Check that the sender\'s email address is legitimate</li>
    <li>Scan the attachment with antivirus if available</li>
    <li>Be cautious of file types like .exe, .zip, .scr</li>
    <li>When in doubt, contact the sender through another method</li>
</ol>

<p><strong>Red Flags:</strong></p>
<ul>
    <li>Unexpected attachments from known contacts</li>
    <li>Generic greetings like "Dear User"</li>
    <li>Urgent language pressuring you to open immediately</li>
    <li>Suspicious file names or extensions</li>
</ul>

<p>Better safe than sorry - forward suspicious emails to IT for verification.</p>

<p>Stay vigilant,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Creating a Security-Conscious Work Environment',
                'body' => '<p>Hello {{user_name}},</p>

<p>Security is most effective when we all work together. Here\'s how you can contribute to a security-conscious workplace culture.</p>

<p><strong>Be a Security Champion:</strong></p>
<ul>
    <li>Lead by example - follow security policies consistently</li>
    <li>Share security tips with colleagues</li>
    <li>Speak up when you notice security concerns</li>
    <li>Participate in security training and stay informed</li>
    <li>Support new team members in learning our security practices</li>
</ul>

<p><strong>Encourage Open Communication:</strong><br>
Make it easy for others to report security concerns without fear. We all make mistakes - what matters is that we learn and improve together.</p>

<p>Thank you for being part of our security culture!</p>

<p>With appreciation,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'USB Drive Security Guidelines',
                'body' => '<p>Dear {{user_name}},</p>

<p>USB drives are convenient but can pose security risks if not used properly. Here\'s how to use them safely.</p>

<p><strong>USB Drive Best Practices:</strong></p>
<ul>
    <li>Only use company-issued USB drives for work data</li>
    <li>Never plug in USB drives found lying around</li>
    <li>Scan USB drives with antivirus before accessing files</li>
    <li>Encrypt sensitive data stored on USB drives</li>
    <li>Keep track of your USB drives - report lost drives immediately</li>
    <li>Properly erase USB drives before disposing of them</li>
</ul>

<p><strong>Why This Matters:</strong><br>
USB drives can contain malware and lost drives can expose sensitive company information. These simple precautions keep everyone safe.</p>

<p>Questions? Contact IT anytime.</p>

<p>Best regards,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Video Conference Security Tips',
                'body' => '<p>Hi {{user_name}},</p>

<p>Video conferencing has become essential for our work. Here are some tips to keep your virtual meetings secure and professional.</p>

<p><strong>Secure Video Conferencing:</strong></p>
<ul>
    <li>Use meeting passwords and waiting rooms for sensitive discussions</li>
    <li>Don\'t share meeting links publicly or on social media</li>
    <li>Verify participants before discussing confidential matters</li>
    <li>Use the latest version of conferencing software</li>
    <li>Be aware of what\'s visible in your background</li>
    <li>Mute when not speaking to avoid accidental disclosures</li>
    <li>End meetings properly and don\'t leave them running</li>
</ul>

<p>These simple habits ensure productive and secure virtual meetings.</p>

<p>Happy conferencing,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Protecting Your Digital Identity',
                'body' => '<p>Hello {{user_name}},</p>

<p>Your digital identity is valuable. Here\'s how to protect your personal and professional identity online.</p>

<p><strong>Identity Protection Tips:</strong></p>
<ul>
    <li>Use unique passwords for each account</li>
    <li>Enable two-factor authentication wherever possible</li>
    <li>Be cautious about what you share on social media</li>
    <li>Review privacy settings on all accounts regularly</li>
    <li>Monitor your accounts for suspicious activity</li>
    <li>Be skeptical of requests for personal information</li>
</ul>

<p><strong>If Your Identity Is Compromised:</strong><br>
Change passwords immediately, notify affected services, and contact IT if it involves work accounts. Quick action minimizes damage.</p>

<p>Your identity security matters to us!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Cloud Storage Security Guidelines',
                'body' => '<p>Dear {{user_name}},</p>

<p>Cloud storage is a powerful tool for collaboration and accessibility. Here\'s how to use it securely.</p>

<p><strong>Secure Cloud Storage Practices:</strong></p>
<ol>
    <li>Use only approved company cloud services</li>
    <li>Enable two-factor authentication on cloud accounts</li>
    <li>Classify data before uploading to the cloud</li>
    <li>Review and manage sharing permissions regularly</li>
    <li>Use strong, unique passwords for cloud accounts</li>
    <li>Be cautious when accessing cloud data on public devices</li>
</ol>

<p><strong>Sharing Files Safely:</strong><br>
When sharing files, use the minimum permissions necessary and set expiration dates when possible. Regularly audit who has access to your files.</p>

<p>Need help with cloud security? We\'re here for you!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Recognizing and Reporting Security Incidents',
                'body' => '<p>Hi {{user_name}},</p>

<p>Quick recognition and reporting of security incidents helps us protect everyone. Here\'s what to watch for and how to report.</p>

<p><strong>Signs of a Security Incident:</strong></p>
<ul>
    <li>Unusual account activity or login alerts</li>
    <li>Unexpected system slowdowns or crashes</li>
    <li>Files that are encrypted, missing, or modified</li>
    <li>Suspicious emails or messages</li>
    <li>Unauthorized access attempts</li>
    <li>Lost or stolen devices</li>
</ul>

<p><strong>How to Report:</strong></p>
<ol>
    <li>Contact IT immediately - don\'t wait</li>
    <li>Provide as many details as possible</li>
    <li>Don\'t try to fix it yourself if you\'re unsure</li>
    <li>Preserve evidence (don\'t delete suspicious emails)</li>
    <li>Follow IT\'s instructions</li>
</ol>

<p>Remember: Reporting quickly is never the wrong choice. We appreciate your vigilance!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Smart Social Media Practices',
                'body' => '<p>Hello {{user_name}},</p>

<p>Social media is great for staying connected, but it\'s important to be mindful of security and privacy. Here are some friendly guidelines.</p>

<p><strong>Social Media Security Tips:</strong></p>
<ul>
    <li>Think before you post - information online is permanent</li>
    <li>Don\'t share details about work projects or systems publicly</li>
    <li>Be cautious about accepting connection requests from strangers</li>
    <li>Review your privacy settings regularly</li>
    <li>Avoid posting about your work schedule or travel plans</li>
    <li>Don\'t share photos that reveal sensitive information</li>
</ul>

<p><strong>Representing Our Company:</strong><br>
When you identify yourself as our employee, remember that your posts reflect on the organization. Keep it professional and positive!</p>

<p>Stay social, stay safe,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Backup Your Data: A Simple Habit That Saves Headaches',
                'body' => '<p>Dear {{user_name}},</p>

<p>Data loss can happen to anyone - hardware failures, accidental deletions, or security incidents. Regular backups are your safety net.</p>

<p><strong>Why Backup Matters:</strong></p>
<ul>
    <li>Protects against hardware failures and corruption</li>
    <li>Enables recovery from ransomware attacks</li>
    <li>Safeguards against accidental deletions</li>
    <li>Provides peace of mind</li>
</ul>

<p><strong>Backup Best Practices:</strong></p>
<ol>
    <li>Follow the 3-2-1 rule: 3 copies, 2 different media, 1 offsite</li>
    <li>Schedule automatic backups when possible</li>
    <li>Test your backups periodically to ensure they work</li>
    <li>Secure your backups with encryption</li>
    <li>Keep backups separate from your main system</li>
</ol>

<p>Need help setting up backups? Contact IT - we\'re happy to help!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Email Signature Phishing Awareness',
                'body' => '<p>Hi {{user_name}},</p>

<p>Did you know that attackers can fake email signatures to make messages look legitimate? Let\'s learn how to spot these fakes.</p>

<p><strong>What to Check:</strong></p>
<ul>
    <li>Verify the actual email address, not just the display name</li>
    <li>Look for inconsistencies in formatting or branding</li>
    <li>Check contact information against official directories</li>
    <li>Be suspicious of unexpected requests, even if the signature looks real</li>
    <li>Hover over links before clicking to see the real destination</li>
</ul>

<p><strong>Real-World Example:</strong><br>
An email might show "CEO Name &lt;ceo@company.com&gt;" in the display, but the actual address could be "ceo@company-secure.net". Always check the real address!</p>

<p>When in doubt, verify through another communication channel.</p>

<p>Stay aware,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Secure Your Home Office',
                'body' => '<p>Hello {{user_name}},</p>

<p>Working from home is convenient, but it requires extra security awareness. Here\'s how to keep your home office secure.</p>

<p><strong>Home Office Security Checklist:</strong></p>
<ul>
    <li>Use a secure, password-protected Wi-Fi network</li>
    <li>Keep work and personal devices separate when possible</li>
    <li>Ensure family members understand work device boundaries</li>
    <li>Use a privacy screen if working in shared spaces</li>
    <li>Lock devices when stepping away</li>
    <li>Secure physical documents and work equipment</li>
    <li>Use headphones for confidential calls</li>
</ul>

<p><strong>Network Security:</strong><br>
Change default router passwords, enable WPA3 encryption, and keep router firmware updated.</p>

<p>Your home office security protects company data - thank you!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Multi-Factor Authentication: Your Extra Layer of Protection',
                'body' => '<p>Dear {{user_name}},</p>

<p>Multi-factor authentication (MFA) is one of the best ways to protect your accounts. Even if someone steals your password, MFA keeps them out.</p>

<p><strong>What Is MFA?</strong><br>
MFA requires two or more verification methods: something you know (password), something you have (phone), or something you are (fingerprint).</p>

<p><strong>MFA Methods:</strong></p>
<ul>
    <li><strong>SMS codes:</strong> Text messages with verification codes</li>
    <li><strong>Authenticator apps:</strong> Time-based codes from apps like Microsoft Authenticator</li>
    <li><strong>Biometrics:</strong> Fingerprint or facial recognition</li>
    <li><strong>Hardware tokens:</strong> Physical security keys</li>
</ul>

<p><strong>Best Practices:</strong><br>
Enable MFA on all accounts that support it, especially email and financial accounts. Authenticator apps are more secure than SMS.</p>

<p>Need help setting up MFA? Contact IT!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Vendor and Third-Party Security',
                'body' => '<p>Hi {{user_name}},</p>

<p>We work with many vendors and third parties. Here\'s how to interact with them securely while maintaining good business relationships.</p>

<p><strong>Working Safely with Third Parties:</strong></p>
<ul>
    <li>Verify identities before sharing any company information</li>
    <li>Only share the minimum information necessary</li>
    <li>Use approved channels for communication</li>
    <li>Check that vendors follow security best practices</li>
    <li>Report suspicious requests claiming to be from vendors</li>
    <li>Don\'t grant system access without proper approval</li>
</ul>

<p><strong>Red Flags:</strong><br>
Be cautious if a vendor requests unusual access, uses pressure tactics, or contacts you through unexpected channels.</p>

<p>When in doubt, verify with your manager or the vendor through official contact information.</p>

<p>Thank you for protecting our partnerships!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Encryption: Protecting Data at Rest and in Transit',
                'body' => '<p>Hello {{user_name}},</p>

<p>Encryption protects your data by making it unreadable to unauthorized people. Here\'s what you need to know about using encryption effectively.</p>

<p><strong>Types of Encryption:</strong></p>
<ul>
    <li><strong>At Rest:</strong> Protects stored data on devices and servers</li>
    <li><strong>In Transit:</strong> Protects data being transmitted over networks</li>
</ul>

<p><strong>When to Use Encryption:</strong></p>
<ul>
    <li>Sending sensitive information via email (use encryption tools)</li>
    <li>Storing confidential files on laptops or USB drives</li>
    <li>Transmitting personal or financial information</li>
    <li>Backing up sensitive data</li>
</ul>

<p><strong>Easy Encryption Tools:</strong><br>
We provide encryption tools for email and file storage. Contact IT to learn how to use them - it\'s easier than you think!</p>

<p>Encryption is a powerful protection tool. Let\'s use it!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Incident Response: Your Role in Our Security',
                'body' => '<p>Dear {{user_name}},</p>

<p>Everyone plays a vital role in our security incident response. Here\'s what you need to know about responding to potential security issues.</p>

<p><strong>If You Suspect a Security Incident:</strong></p>
<ol>
    <li><strong>Stay Calm:</strong> Don\'t panic - take a moment to assess</li>
    <li><strong>Contain:</strong> Disconnect from network if you suspect malware</li>
    <li><strong>Document:</strong> Note what happened and when</li>
    <li><strong>Report:</strong> Contact IT immediately with details</li>
    <li><strong>Preserve:</strong> Don\'t delete files or clear history</li>
    <li><strong>Follow Instructions:</strong> IT will guide you through next steps</li>
</ol>

<p><strong>What Not to Do:</strong></p>
<ul>
    <li>Don\'t try to fix it yourself unless instructed</li>
    <li>Don\'t shut down your computer immediately</li>
    <li>Don\'t discuss the incident on social media</li>
    <li>Don\'t assume it\'s nothing - report it</li>
</ul>

<p>Your quick response helps us protect everyone. Thank you!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Privacy Awareness: Protecting Personal Information',
                'body' => '<p>Hi {{user_name}},</p>

<p>Privacy and security go hand in hand. Here\'s how we protect personal information for our employees, customers, and partners.</p>

<p><strong>Personal Information We Protect:</strong></p>
<ul>
    <li>Names, addresses, and contact information</li>
    <li>Financial and payment information</li>
    <li>Health and medical records</li>
    <li>Employment and HR data</li>
    <li>Account credentials and access information</li>
</ul>

<p><strong>Your Privacy Responsibilities:</strong></p>
<ul>
    <li>Collect only the minimum data needed</li>
    <li>Use and share data only for authorized purposes</li>
    <li>Store personal information securely</li>
    <li>Delete data when no longer needed</li>
    <li>Report privacy incidents immediately</li>
    <li>Respect individuals\' privacy rights</li>
</ul>

<p><strong>Remember:</strong> Handle others\' information the way you\'d want your own information handled.</p>

<p>Thank you for respecting privacy!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Clean Desk Policy: Security Beyond the Screen',
                'body' => '<p>Hello {{user_name}},</p>

<p>A clean desk isn\'t just about organization - it\'s an important security practice that protects sensitive information.</p>

<p><strong>Clean Desk Essentials:</strong></p>
<ul>
    <li>Lock away confidential documents when not in use</li>
    <li>Don\'t leave sensitive information visible on your desk</li>
    <li>Secure laptops and mobile devices, even at your desk</li>
    <li>Log out or lock your computer when leaving</li>
    <li>Shred documents before disposing of them</li>
    <li>Clear your desk at the end of each day</li>
</ul>

<p><strong>Why This Matters:</strong><br>
Visitors, cleaning staff, and even colleagues might inadvertently see confidential information left on desks. Simple precautions prevent unnecessary exposure.</p>

<p><strong>Quick Tips:</strong><br>
Use a locked drawer for sensitive materials, position monitors away from windows and doors, and use privacy screens if needed.</p>

<p>A clean desk is a secure desk!</p>

<p>simUphish Team</p>'
            ],
            [
                'subject' => 'Security Awareness: A Continuous Journey',
                'body' => '<p>Dear {{user_name}},</p>

<p>Security awareness isn\'t a one-time training - it\'s an ongoing journey. Here\'s how to stay sharp and security-minded every day.</p>

<p><strong>Daily Security Habits:</strong></p>
<ul>
    <li>Start each day with security in mind</li>
    <li>Question unexpected requests or unusual communications</li>
    <li>Take a moment to verify before clicking or sharing</li>
    <li>Stay informed about emerging threats</li>
    <li>Share security tips with your team</li>
    <li>Participate in security training and updates</li>
</ul>

<p><strong>Building Security Culture:</strong><br>
Security is strongest when we all work together. Support your colleagues, ask questions, and make security a normal part of conversations.</p>

<p><strong>Resources Available:</strong></p>
<ul>
    <li>Monthly security newsletters</li>
    <li>IT helpdesk for questions anytime</li>
    <li>Security awareness training portal</li>
    <li>Regular security tips and updates</li>
</ul>

<p>Thank you for being a security-conscious team member!</p>

<p>With appreciation,<br>simUphish Team</p>'
            ],
            [
                'subject' => 'Secure Printing and Document Handling',
                'body' => '<p>Hi {{user_name}},</p>

<p>Printed documents can contain sensitive information. Here are best practices for secure printing and document handling.</p>

<p><strong>Secure Printing Practices:</strong></p>
<ul>
    <li>Use secure/follow-me printing when available</li>
    <li>Collect printed documents immediately</li>
    <li>Don\'t leave sensitive documents in printers or copiers</li>
    <li>Clear printer memory for sensitive jobs</li>
    <li>Verify you\'re at the correct printer before releasing jobs</li>
</ul>

<p><strong>Document Handling:</strong></p>
<ul>
    <li>Mark confidential documents appropriately</li>
    <li>Use secure mail or hand delivery for sensitive documents</li>
    <li>Shred confidential documents - don\'t just throw them away</li>
    <li>Track important documents with logs or receipts</li>
    <li>Store sensitive documents in locked cabinets</li>
</ul>

<p><strong>Before Leaving:</strong><br>
Always check printers and copiers for your documents before walking away.</p>

<p>Secure printing protects information!</p>

<p>simUphish Team</p>'
            ]
        ];
    }
};
