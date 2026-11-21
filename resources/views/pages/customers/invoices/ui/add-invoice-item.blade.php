<form class="form" id="form_validation" method="post" action="">
  @csrf
  <div class="row clearfix">
    <x-form.form-input :label="__('Invoice Item')" name="invoice_item" required colspan="4" />
    <x-form.form-input :label="__('Unit Cost (Rate)')" type="number" name="rate" required colspan="4" />
    <x-form.form-input :label="__('Quantity')" type="number" name="qty" required colspan="4" />
    <x-form.form-input :label="__('Description')" name="description" required />

    {{-- ACTION --}}
    <x-form.form-button as="Add" />
  </div>
</form>