<div class="pagination-and-action-area body">
  <div>
    <div class="select-and-apply-area">
      <div class="form-group width-300">
        <div class="form-line">
          <select class="form-control" name="with_selected" id="">
            <option value="0" hidden>{{ __('ui.actions.with_selected') }}</option>
            <option value="trash">{{ __('root.common.move_to_trash') }}</option>
            <option value="delete">{{ __('root.common.permanently_delete') }}</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <button type="submit" name="with_selected_action" class="btn btn-sm btn-info waves-effect">
          {{ __('root.common.apply') }}
        </button>
      </div>
    </div>
  </div>
  <div>
    <div class="custom-paginate">
      {{ $slot }}
    </div>
  </div>
</div>