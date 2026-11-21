@props(['all' => 0, 'trash' => 0])

<div class="header">
  {{-- LEFT SIDE --}}
  <a href="{{ url()->current() }}" class="btn btn-xs btn-info waves-effect">
    {{ __('root.common.all') }} ({{ $all }})
  </a>
  {{-- --}}
  <a href="{{ url()->current() . '?trash=true' }}" @class([ 'btn btn-xs btn-danger waves-effect text-black'
    , 'dis-none'=> $trash < 1 ])>
      {{ __('root.common.trash') }} ({{ $trash }})
  </a>

  {{-- RIGHT SIDE --}}
  <ul class="header-dropdown m-r--5">
    <form class="search" action="" method="get">
      @csrf
      <input type="search" name="search" class="form-control input-sm" placeholder="{{ trim(__('root.common.search')) }} ( / )" required />
    </form>
  </ul>
</div>