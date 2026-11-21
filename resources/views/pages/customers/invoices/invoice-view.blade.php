@extends('layouts.table-layout')

@section('title', $title)

@section('container')
    <x-breadcrumb :level="$resource" :location="$title" :as="$type" />
    <div class="row">
        <div class="col-xs-12">
            <div class="card">
                <x-table.table-search-bar :all="count($customerInvoices)" :trash="0" />
                <form class="actionForm" action="" method="get">
                    @csrf
                    <x-table.table-pagination-with-selected />
                    <x-table.table-wrapper :thead="['#', __('financial.labels.invoice_number'), __('financial.terms.customer'), __('financial.labels.invoice_date'), __('financial.terms.total_amount'), __('financial.labels.balance_due'), __('financial.labels.actions')]">
                        @forelse ($customerInvoices as $invoice)
                            @php
                                $sn = $loop->iteration;
                                $total_amount = number_format($invoice->total_amount, 2);
                            @endphp
                            <tr>
                                <x-table.table-checkbox :sn="$sn" :item="$invoice" />
                                <td style="font-size: 12px;">{{ $sn }}</td>
                                <td style="font-size: 12px;"><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td style="font-size: 12px;">{{ $invoice->customer->name }}</td>
                                <td style="font-size: 12px;">{{ $invoice->invoice_date }}</td>
                                <td style="font-size: 12px;"><strong>{{ $currency }}{{ $total_amount }}</strong></td>
                                <td style="font-size: 12px;"><strong>@if($invoice->current_balance_cents === null){{ __('financial.labels.historical_invoice_requires_review') }}@else{{ $currency }}{{ number_format($invoice->current_balance_cents / 100, 2) }}@endif</strong></td>
                                <td style="font-size: 12px;">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('customers.invoices.pdf', $invoice->id) }}"
                                           class="btn btn-sm btn-primary" 
                                           title="{{ __('financial.labels.view_pdf') }}"
                                           target="_blank">
                                            <i class="fa fa-file-pdf-o"></i> PDF
                                        </a>
                                        <a href="javascript:void(0)" 
                                           class="btn btn-sm btn-info" 
                                           title="{{ __('financial.labels.view_invoice') }}"
                                           onclick="viewInvoiceModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}')">
                                            <i class="fa fa-eye"></i> {{ __('financial.labels.view_invoice') }}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <x-table.table-no-records />
                                </td>
                            </tr>
                        @endforelse
                    </x-table.table-wrapper>
                    <x-table.table-pagination-with-selected />
                </form>
            </div>
        </div>
    </div>

    <!-- Invoice View Modal -->
    <div id="invoiceModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoiceModalTitle">{{ __('financial.labels.invoice_details') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('ui.actions.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="invoiceModalBody">
                    <!-- Invoice details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('ui.actions.close') }}</button>
                    <a id="invoicePdfLink" href="#" class="btn btn-primary" target="_blank">
                        <i class="fa fa-file-pdf-o"></i> {{ __('financial.labels.download_pdf') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-group {
            display: flex;
            gap: 4px;
        }
        .modal-body {
            max-height: 400px;
            overflow-y: auto;
        }
        .invoice-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .invoice-detail-label {
            font-weight: 600;
            color: #555;
            width: 35%;
        }
        .invoice-detail-value {
            width: 65%;
            text-align: right;
        }
        .invoice-items-table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        .invoice-items-table th,
        .invoice-items-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .invoice-items-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }
    </style>

    <script>
        function viewInvoiceModal(invoiceId, invoiceNumber) {
            // Update modal title
            document.getElementById('invoiceModalTitle').textContent = @json(__('financial.labels.invoice_number')) + ' #' + invoiceNumber;
            
            // Update PDF link
            document.getElementById('invoicePdfLink').href = '/invoices/' + invoiceId + '/pdf';
            
            // For now, show a loading message
            document.getElementById('invoiceModalBody').innerHTML = '<p class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + @json(__('financial.labels.loading_invoice_details')) + '</p>'; 
            
            // Show modal
            $('#invoiceModal').modal('show');
            
            // In a real implementation, you would fetch invoice details via AJAX
            // For now, we'll show a simplified view
            setTimeout(function() {
                document.getElementById('invoiceModalBody').innerHTML = `
                    <div class="invoice-detail-row">
                        <span class="invoice-detail-label">${@json(__('financial.labels.invoice_number'))}:</span>
                        <span class="invoice-detail-value"><strong>${invoiceNumber}</strong></span>
                    </div>
                    <div class="invoice-detail-row">
                        <span class="invoice-detail-label">${@json(__('financial.labels.status'))}:</span>
                        <span class="invoice-detail-value"><span class="label label-primary">${@json(__('financial.labels.active'))}</span></span>
                    </div>
                    <p class="text-muted" style="margin-top: 15px; text-align: center;">
                        <i class="fa fa-info-circle"></i> ${@json(__('financial.labels.click_download_pdf_details'))}
                    </p>
                `;
            }, 300);
        }
    </script>
@endsection
