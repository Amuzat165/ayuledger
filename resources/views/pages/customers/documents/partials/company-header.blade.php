@php($hasHeaderImage = !empty($header_image) && file_exists(public_path($header_image)))
@php($invoiceCreationDocument = $invoice_creation_document ?? false)
<header class="document-header">
    @if($hasHeaderImage)
        <div class="document-banner-container" style="width:100%;text-align:center;margin:0 0 12px;">
            <img class="document-banner" src="{{ $invoiceCreationDocument ? asset($header_image) : public_path($header_image) }}" alt="{{ __('financial.labels.header_image') }}" style="{{ $invoiceCreationDocument ? 'display:block;width:auto;max-width:100%;height:auto;max-height:108px;object-fit:contain;object-position:center;margin:0 auto;' : 'display:block;width:100%;max-width:100%;height:auto;' }}">
        </div>
    @endif
    <div class="company-row" @if($hasHeaderImage) style="display:block;text-align:center;font-size:9px;line-height:1.35;margin:0 auto 12px;" @endif>
        @if(!$hasHeaderImage && !empty($company_logo) && file_exists(public_path($company_logo)))
            <img class="company-logo" src="{{ $invoiceCreationDocument ? asset($company_logo) : public_path($company_logo) }}" alt="{{ __('ui.settings.company_logo') }}">
        @endif
        <div class="company-copy">
            <div class="company-name" @if($hasHeaderImage) style="font-size:9px;font-weight:normal;color:inherit;" @endif>{{ $company_name ?: 'AYUGLOBAL' }}</div>
            @php($companyAddress = collect([$address_1 ?? null, $address_2 ?? null, $city ?? null, $state ?? null, $zip_code ?? null])->filter()->implode(', '))
            @if($companyAddress)<div>{{ $companyAddress }}</div>@endif
            @if(!empty($contact_person))<div>{{ __('ui.settings.contact_person') }}: {{ $contact_person }}</div>@endif
            @if(!empty($phone) || !empty($email))
                <div>{{ __('financial.labels.receipt_contact_details', ['phone' => $phone ?? '', 'email' => $email ?? '']) }}</div>
            @endif
        </div>
    </div>
</header>
