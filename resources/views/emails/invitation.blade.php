@component('mail::message')
# You've Been Invited!

You have been invited to join {{ config('app.name') }} to help manage and categorize credit card transactions.

@component('mail::button', ['url' => route('invitations.accept', $invitation->token)])
Accept Invitation
@endcomponent

This invitation link will expire in 7 days.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 