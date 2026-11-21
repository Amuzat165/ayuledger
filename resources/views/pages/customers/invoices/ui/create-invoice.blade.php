<form class="form" id="form_validation" method="post" action="{{ route('customers.invoices.store') }}">
    @csrf
    <div class="row clearfix">
        <x-form.form-select  :label="__('financial.labels.select_customer')" name="customer_id" :options="$params->customer_id_options" required colspan="3" />
    </div>
    <div class="row clearfix">
        {{-- CUSTOMER INFO --}}
        <x-form.form-input  :label="__('financial.labels.customer_name')" name="name" required colspan="3" />
        <x-form.form-input  :label="__('financial.screens.email_address')" type="email" name="email" required colspan="3" />
        <x-form.form-input  :label="__('financial.screens.phone_number')" type="tel" name="phone" required colspan="3" />
        <x-form.form-input  :label="__('financial.labels.home_address')" name="address" required colspan="3" />

        {{-- INVOICE INFO --}}
        <x-form.form-input  :label="__('financial.labels.branch')" name="branch_name" required colspan="3" />
        <x-form.form-input  :label="__('financial.labels.invoice_number')" type="number" name="invoice_number" required colspan="3" value="" readonly />
        <x-form.form-input  :label="__('financial.labels.invoice_date')" type="date" name="invoice_date" required colspan="3" value="" />
        <x-form.form-input  :label="__('financial.labels.exchange_rate')" name="exchange_rate" required colspan="3" />

        {{-- INVOICE ITEMS --}}
        <div class="col-12">
            <table class="table table-bordered" id="invoice_items_table">
                <thead>
                <tr>
                    <th>{{ __('financial.labels.item') }}</th>
                    <th>{{ __('financial.terms.description') }}</th>
                    <th>{{ __('financial.terms.quantity') }}</th>
                    <th>{{ __('financial.labels.rate') }}</th>
                    <th>{{ __('financial.terms.total_amount') }}</th>
                    <th>{{ __('financial.labels.action') }}</th>
                </tr>
                </thead>
                <tbody>
                <!-- Initial row -->
                <tr>
                    <td>
                        <select name="items[0][item_id]" class="form-control item-select" onchange="setItemDescriptionAndRate(this)">
                            <option value="">{{ __('financial.labels.select_item') }}</option>
                            @foreach($params->inventory_items as $item)
                                <option value="{{ $item->id }}" data-description="{{ $item->description }}" data-rate="{{ $item->sale_price }}">{{ $item->type_name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td><input type="text" name="items[0][description]" class="form-control description" readonly></td>
                    <td><input type="number" name="items[0][quantity]" class="form-control" oninput="calculateAmount(this)"></td>
                    <td><input type="number" name="items[0][rate]" class="form-control rate" readonly></td>
                    <td><input type="number" name="items[0][amount]" class="form-control amount" readonly></td>
                    <td><button type="button" class="btn btn-danger delete-row">{{ __('ui.actions.delete') }}</button></td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4" align="right"><strong>{{ __('financial.terms.subtotal') }}:</strong></td>
                    <td><input type="number" id="total_amount" class="form-control" readonly></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4" align="right"><strong>{{ __('financial.terms.vat') }} ({{ $params->vat_percentage ?? 0 }}%):</strong></td>
                    <td><input type="number" id="vat_amount" class="form-control" readonly></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4" align="right"><strong>{{ __('financial.terms.total') }}:</strong></td>
                    <td><input type="number" id="total_due" class="form-control" readonly></td>
                    <td></td>
                </tr>
                @if($params->discount_enabled ?? false)
                <tr>
                    <td colspan="4" align="right"><strong>{{ __('financial.labels.discount_percentage') }}</strong></td>
                    <td><input type="number" id="discount_percentage" class="form-control" min="0" max="100" step="0.01" value="{{ number_format((float) ($params->discount_percentage ?? 0), 2, '.', '') }}" readonly></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4" align="right"><strong>{{ __('financial.labels.discount_amount') }}:</strong></td>
                    <td><input type="number" id="discount_amount" class="form-control" readonly></td>
                    <td></td>
                </tr>
                @endif
                <tr>
                    <td colspan="4" align="right"><strong>{{ __('financial.labels.balance_due') }}:</strong></td>
                    <td><input type="number" id="balance_due" class="form-control" readonly></td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
            <button type="button" class="btn btn-primary" id="add_row">{{ __('financial.screens.add_row') }}</button>
        </div>

        {{-- ACTION --}}
        <x-form.form-button as="Create" />
    </div>
</form>

<style>
    .hidden-label {
        display: none;
    }
</style>

<script>
    // Function to handle setting item description and rate based on selected item
    function setItemDescriptionAndRate(select) {
        const selectedOption = select.options[select.selectedIndex];
        const row = select.closest('tr');
        const descriptionField = row.querySelector('.description');
        const rateField = row.querySelector('.rate');

        if (selectedOption) {
            descriptionField.value = selectedOption.getAttribute('data-description');
            rateField.value = selectedOption.getAttribute('data-rate');
            calculateAmount(select); // Update the amount based on quantity and rate
        } else {
            descriptionField.value = '';
            rateField.value = '';
        }
    }

    // Function to calculate amount based on quantity and rate
    function calculateAmount(element) {
        const row = element.closest('tr');
        const quantity = parseFloat(row.querySelector('input[name$="[quantity]"]').value) || 0;
        const rate = parseFloat(row.querySelector('input[name$="[rate]"]').value) || 0;
        const amountField = row.querySelector('input[name$="[amount]"]');
        const amount = quantity * rate;
        amountField.value = amount.toFixed(2);

        updateTotalAndBalance();
    }

    function updateTotalAndBalance() {
        let total = 0;
        document.querySelectorAll('input[name$="[amount]"]').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        const subtotalCents = Math.round(total * 100);
        document.getElementById('total_amount').value = (subtotalCents / 100).toFixed(2);

        // Preview only; server calculations remain authoritative.
        const vatPercentage = Number(@json($params->vat_percentage ?? 0));
        const vatCents = Math.round(subtotalCents * vatPercentage / 100);
        const totalCents = subtotalCents + vatCents;
        document.getElementById('vat_amount').value = (vatCents / 100).toFixed(2);
        document.getElementById('total_due').value = (totalCents / 100).toFixed(2);

        const discountInput = document.getElementById('discount_percentage');
        let discountCents = 0;
        if (discountInput) {
            const discountPercentage = Number(discountInput.value) || 0;
            discountCents = Math.round(totalCents * discountPercentage / 100);
            document.getElementById('discount_amount').value = (discountCents / 100).toFixed(2);
        }
        document.getElementById('balance_due').value = ((totalCents - discountCents) / 100).toFixed(2);
    }

    // Attach event listeners after the DOM content is loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Function to populate customer fields based on selected customer ID
        const customers = @json($params->customers);

        document.querySelector('select[name="customer_id"]').addEventListener('change', function () {
            const customerId = this.value;
            const customer = customers.find(c => c.id == customerId);

            if (customer) {
                populateField('name', customer.name);
                populateField('email', customer.email);
                populateField('phone', customer.phone);
                populateField('address', customer.address);
                populateField('branch_name', customer.branch_id ? customer.branch.name : '');


                // Generate the next invoice number and set the current date
                const latestInvoiceNumber = @json($params->next_invoice_number);
                const nextInvoiceNumber = latestInvoiceNumber ? String(parseInt(latestInvoiceNumber) + 1).padStart(4, '0') : '0001';
                populateField('invoice_number', nextInvoiceNumber);
                populateField('invoice_date', new Date().toISOString().split('T')[0]);
            }
        });

        // Function to populate form fields
        function populateField(fieldName, value) {
            const field = document.querySelector(`input[name="${fieldName}"]`);
            const label = field.closest('.form-group').querySelector('label');
            field.value = value;
            if (value) {
                label.classList.add('hidden-label');
            } else {
                label.classList.remove('hidden-label');
            }
        }

        // Function to handle adding new rows to the invoice items table
        document.getElementById('add_row').addEventListener('click', function () {
            const table = document.getElementById('invoice_items_table').getElementsByTagName('tbody')[0];
            const rowCount = table.rows.length;
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td>
                    <select name="items[${rowCount}][item_id]" class="form-control item-select" onchange="setItemDescriptionAndRate(this)">
                        <option value="">{{ __('financial.labels.select_item') }}</option>
                        @foreach($params->inventory_items as $item)
            <option value="{{ $item->id }}" data-description="{{ $item->description }}" data-rate="{{ $item->sale_price }}">{{ $item->type_name }}</option>
                        @endforeach
            </select>
        </td>
        <td><input type="text" name="items[${rowCount}][description]" class="form-control description" readonly></td>
                <td><input type="number" name="items[${rowCount}][quantity]" class="form-control" oninput="calculateAmount(this)"></td>
                <td><input type="number" name="items[${rowCount}][rate]" class="form-control rate" readonly></td>
                <td><input type="number" name="items[${rowCount}][amount]" class="form-control amount" readonly></td>
                <td><button type="button" class="btn btn-danger delete-row">{{ __('ui.actions.delete') }}</button></td>
            `;
            attachDeleteHandler();
        });

        // Function to attach delete row handler to delete buttons
        function attachDeleteHandler() {
            const deleteButtons = document.querySelectorAll('.delete-row');
            deleteButtons.forEach(button => {
                button.removeEventListener('click', handleDeleteRow);
                button.addEventListener('click', handleDeleteRow);
            });
        }

        // Function to handle deleting rows from invoice items table
        function handleDeleteRow(event) {
            const row = event.target.closest('tr');
            row.remove();
            updateTotalAndBalance(); // Recalculate total and balance due after removing row
        }

        attachDeleteHandler(); // Attach delete row handler on page load
        updateTotalAndBalance();
    });
</script>
