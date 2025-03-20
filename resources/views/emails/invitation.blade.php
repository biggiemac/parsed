@component('mail::message')
# You've Been Invited!

{{ $inviterName }} has invited you to join **{{ $organizationName }}** as a {{ $role }} on {{ config('app.name') }} to help manage and categorize credit card transactions.

As a {{ $role }}, you will be able to:
@if($role === 'Admin')
- View and manage all transactions
- Create and manage categories
- Invite new members
- Manage organization settings
@else
- View and manage transactions
- Use existing categories
- View reports and analytics
@endif

@component('mail::button', ['url' => $acceptUrl])
Accept Invitation
@endcomponent

This invitation link will expire on {{ $expiresAt }}.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 