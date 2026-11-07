<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OJT Performance Evaluation Request</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 24px; margin: 0;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #33954B; padding: 24px 32px; text-align: left;">
                            <h2 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700; letter-spacing: -0.025em;">OJT SYNC Internship Evaluation</h2>
                            <p style="color: #dcfce7; margin: 4px 0 0 0; font-size: 13px;">Official Industry Supervisor Assessment</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px;">
                            <p style="font-size: 15px; line-height: 1.5; margin: 0 0 16px 0;">
                                Dear <strong>{{ $evaluation->supervisor_name ?? 'Supervisor' }}</strong>,
                            </p>
                            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 20px 0;">
                                You have been designated as the immediate industry supervisor for <strong>{{ $student->user->name }}</strong> (Student ID: {{ $student->student_no ?? 'N/A' }}), interning as <strong>{{ $job->title ?? 'OJT Trainee' }}</strong> at <strong>{{ $company->name ?? 'our company' }}</strong>.
                            </p>

                            @if($customNote)
                                <div style="background-color: #f1f5f9; border-left: 4px solid #33954B; padding: 12px 16px; margin: 0 0 24px 0; border-radius: 4px;">
                                    <p style="margin: 0; font-size: 13px; color: #334155; font-style: italic;">
                                        "{{ $customNote }}"
                                    </p>
                                    <p style="margin: 4px 0 0 0; font-size: 11px; color: #64748b; font-weight: 600;">— Message from HR</p>
                                </div>
                            @endif

                            <p style="font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 24px 0;">
                                Please take a few moments to rate the intern's work ethics, technical competence, initiative, and communication skills using the single-use evaluation link below. No login or password is required.
                            </p>

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 32px 0;">
                                <a href="{{ $evaluationUrl }}" style="background-color: #33954B; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;">
                                    Fill Out Intern Evaluation Form &rarr;
                                </a>
                            </div>

                            <p style="font-size: 12px; color: #94a3b8; line-height: 1.5; margin: 24px 0 0 0; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                                Alternatively, you can copy and paste this secure token link into your browser:<br>
                                <a href="{{ $evaluationUrl }}" style="color: #33954B; word-break: break-all;">{{ $evaluationUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 16px 32px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
                            OJT Sync &bull; University & Industry Internship Management System
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

