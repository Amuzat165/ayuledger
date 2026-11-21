<footer class="document-footer">
    @if(!empty($phone) || !empty($email))
        <div>{{ __('financial.labels.receipt_contact_details', ['phone' => $phone ?? '', 'email' => $email ?? '']) }}</div>
    @endif
    <div>{{ $document_footer_message }}</div>
</footer>
