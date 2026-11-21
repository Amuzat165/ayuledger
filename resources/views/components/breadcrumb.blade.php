@php
$is_form = $as == 'form';
$breadcrumbDisplay = function ($value) {
    $slug = \Illuminate\Support\Str::snake(trim((string) $value));
    foreach (['screens', 'reports', 'terms', 'labels'] as $group) {
        $key = 'financial.' . $group . '.' . $slug;
        if (\Illuminate\Support\Facades\Lang::has($key, 'main')) {
            return __($key);
        }
    }
    return $value;
};
$props = (object)[
'left' => [
'href' => $is_form ? url()->previous() : url()->current() . '/create',
'text' => $is_form ? __('financial.labels.back') : __('financial.labels.add_new'),
'icon' => $is_form ? 'fas fa-chevron-left' : 'fas fa-plus',
],
'right' => [
'icon' => $is_form ? 'fas fa-plus-circle' : 'fas fa-list',
],
];
@endphp

{{-- LEFT SIDE --}}
<div class="block-header @if ($is_rtl) pull-right @else pull-left @endif">
  <a class="btn btn-sm btn-info waves-effect" href="{{ $props->left['href'] }}">
    <i class="{{ $props->left['icon'] }}" style="font-size:12px;float:left;margin-top:1px;"></i> &nbsp;
    {{ $props->left['text'] }}
  </a>
</div>

{{-- RIGHT SIDE --}}
<ol class="breadcrumb breadcrumb-col-cyan @if ($is_rtl) pull-left  @else pull-right @endif">
  <li>
    <a href="{{ route('dashboard') }}">
      <i class="fas fa-home"></i> &nbsp;
      {{ __('financial.labels.home') }}
    </a>
  </li>
  <li>
    <a href="" title="{{ __('Reload') }}">
      <i class="fas fa-folder-open"></i> &nbsp;
      {{ $breadcrumbDisplay($level) }}
    </a>
  </li>
  <li class="active" style="cursor:default">
    <i class="{{ $props->right['icon']}}"></i> &nbsp;
    {{ $breadcrumbDisplay($location) }}
  </li>
</ol>