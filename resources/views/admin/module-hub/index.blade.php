@extends('layouts.app')

@section('title', $title)

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content module-hub">
    <div class="container-fluid">
        <div class="module-intro">
            <p class="module-eyebrow">{{ $eyebrow }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $description }}</p>
        </div>

        <label class="module-search"><i class="fas fa-search"></i><input id="module-search" type="search" placeholder="Search {{ strtolower($title) }}..." aria-label="Search {{ $title }}"></label>

        <div class="module-grid" id="module-grid">
            @foreach ($categories as $category)
                <section class="module-category">
                    <div class="module-heading"><h2>{{ $category['title'] }}</h2><span>{{ count($category['items']) }} links</span></div>
                    <div class="module-cards">
                        @foreach ($category['items'] as $item)
                            <a class="module-card" href="{{ route($item[0]) }}" data-module-name="{{ strtolower($item[1] . ' ' . $item[2]) }}">
                                <span class="module-icon"><i class="fas {{ $item[3] }}"></i></span>
                                <span class="module-copy"><strong>{{ $item[1] }}</strong><small>{{ $item[2] }}</small></span>
                                <i class="fas fa-arrow-right module-arrow"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
        <p class="module-empty" id="module-empty">No links match your search.</p>
    </div>
</section>
@stop

@push('include-css')
<style>
    .module-hub { background: #f6f9fa; min-height: calc(100vh - 66px); }
    .module-intro { margin: 8px 0 25px; }
    .module-eyebrow { color: #0f766e; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; margin: 0 0 8px; text-transform: uppercase; }
    .module-intro h1 { color: #183b56; font-size: 30px; font-weight: 600; margin: 0 0 8px; }
    .module-intro p:last-child { color: #6b7c87; font-size: 14px; margin: 0; }
    .module-search { background: #fff; border: 1px solid #d9e3e8; border-radius: 5px; display: block; max-width: 560px; padding: 0 15px; }
    .module-search i { color: #0f766e; }
    .module-search input { border: 0; color: #183b56; height: 46px; outline: 0; padding: 0 12px; width: calc(100% - 30px); }
    .module-grid { display: grid; gap: 25px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 30px; }
    .module-category { min-width: 0; }
    .module-heading { align-items: baseline; border-bottom: 1px solid #d9e3e8; display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 9px; }
    .module-heading h2 { color: #183b56; font-size: 15px; font-weight: 600; margin: 0; }
    .module-heading span { color: #91a0a7; font-size: 11px; }
    .module-cards { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .module-card { align-items: center; background: #fff; border: 1px solid #d9e3e8; border-radius: 5px; display: flex; min-height: 92px; padding: 14px; text-decoration: none !important; transition: border-color .15s ease, transform .15s ease; }
    .module-card:hover { border-color: #0f766e; transform: translateY(-2px); }
    .module-icon { align-items: center; background: #e4f2f0; border-radius: 4px; color: #0f766e; display: flex; flex: 0 0 34px; height: 34px; justify-content: center; }
    .module-copy { display: flex; flex: 1; flex-direction: column; margin: 0 10px; min-width: 0; }
    .module-copy strong { color: #183b56; font-size: 12px; font-weight: 600; }
    .module-copy small { color: #7b8b94; font-size: 11px; line-height: 1.35; margin-top: 5px; }
    .module-arrow { color: #9aabb2; font-size: 11px; }
    .module-empty { color: #7b8b94; display: none; margin-top: 35px; text-align: center; }
    @media (max-width: 900px) { .module-grid { grid-template-columns: 1fr; } }
    @media (max-width: 540px) { .module-cards { grid-template-columns: 1fr; } .module-intro h1 { font-size: 25px; } }
</style>
@endpush

@push('include-js')
<script>
    (function () {
        var search = document.getElementById('module-search');
        var empty = document.getElementById('module-empty');
        if (!search) return;
        search.addEventListener('input', function () {
            var term = search.value.toLowerCase().trim();
            var visible = 0;
            document.querySelectorAll('.module-category').forEach(function (category) {
                var cards = category.querySelectorAll('.module-card');
                var categoryVisible = 0;
                cards.forEach(function (card) {
                    var match = card.dataset.moduleName.indexOf(term) !== -1;
                    card.style.display = match ? 'flex' : 'none';
                    categoryVisible += match ? 1 : 0;
                });
                category.style.display = categoryVisible ? '' : 'none';
                visible += categoryVisible;
            });
            empty.style.display = visible ? 'none' : 'block';
        });
    }());
</script>
@endpush
