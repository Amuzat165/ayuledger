@extends('layouts.app')

@section('title', 'Site Builder')

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content site-builder">
<div class="site-builder-main">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="page-title mb-1">Website site builder</h1><p class="text-muted mb-0">Manage landing page content, the testimonial carousel, section visibility and plans.</p></div></div>
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<section id="settings" class="panel"><div class="panel-head">AYULEDGERS homepage content</div><div class="panel-body"><form method="POST" action="{{ route('superadmin.site-builder.settings') }}" enctype="multipart/form-data" id="site-settings-form">@csrf<div class="row">@foreach(['tagline'=>'Tagline','hero_title'=>'Hero title','features_title'=>'Features heading','cta_title'=>'Call-to-action title','contact_email'=>'Contact email','whatsapp_url'=>'WhatsApp phone number or link'] as $key=>$label)<div class="col-md-6 mb-3"><label>{{ $label }}</label><input class="form-control" name="{{ $key }}" value="{{ $settings[$key] ?? '' }}"></div>@endforeach<div class="col-md-6 mb-3"><label>Hero text</label><textarea class="form-control" name="hero_text" rows="3">{{ $settings['hero_text'] ?? '' }}</textarea></div><div class="col-md-6 mb-3"><label>Call-to-action text</label><textarea class="form-control" name="cta_text" rows="3">{{ $settings['cta_text'] ?? '' }}</textarea></div><div class="col-md-6 mb-3"><label>Testimonials heading</label><input class="form-control" name="testimonials_title" value="{{ $settings['testimonials_title'] ?? 'Loved by business owners and their teams' }}"></div><div class="col-md-6 mb-3"><label>Testimonials introduction</label><input class="form-control" name="testimonials_text" value="{{ $settings['testimonials_text'] ?? '' }}"></div></div>
<div class="panel mb-3"><div class="panel-head">Business statistics content</div><div class="panel-body"><div class="custom-control custom-checkbox mb-3"><input class="custom-control-input" id="stats-enabled" form="site-settings-form" type="checkbox" name="stats_enabled" value="1" {{ ($settings['stats_enabled'] ?? '1') === '1' ? 'checked' : '' }}><label class="custom-control-label font-weight-bold" for="stats-enabled">Enable business statistics on the landing page</label><small class="d-block text-muted">Save website settings to publish or hide this section.</small></div><div class="row">@foreach(['business_count'=>'Businesses count','business_count_suffix'=>'Businesses suffix','business_count_label'=>'Businesses label','invoice_stat'=>'Optional invoices figure','invoice_stat_label'=>'Invoices caption','uptime_stat'=>'Optional uptime figure','uptime_stat_label'=>'Uptime caption','rating_stat'=>'Optional rating figure','rating_stat_label'=>'Rating caption'] as $key=>$label)<div class="col-md-6 mb-3"><label>{{ $label }}</label><input class="form-control" name="{{ $key }}" value="{{ $settings[$key] ?? (['business_count'=>'12,000','business_count_suffix'=>'+','business_count_label'=>'Businesses run on AYULEDGERS'][$key] ?? '') }}"></div>@endforeach</div></div></div>
<div id="branding" class="row mt-2"><div class="col-md-6 mb-3"><div class="upload-box"><label>Website logo</label><input type="file" class="form-control-file" name="logo" accept="image/png,image/jpeg,image/svg+xml">@if(!empty($settings['logo_path']))<img src="{{ asset('storage/'.$settings['logo_path']) }}" alt="" style="max-height:48px;margin-top:12px">@endif</div></div><div class="col-md-6 mb-3"><div class="upload-box"><label>Browser favicon</label><input type="file" class="form-control-file" name="favicon" accept="image/png,image/jpeg,image/x-icon">@if(!empty($settings['favicon_path']))<img src="{{ asset('storage/'.$settings['favicon_path']) }}" alt="" style="max-height:32px;margin-top:12px">@endif</div></div></div><button class="btn btn-primary">Save website settings</button></form></div></section>
<section id="carousel" class="panel"><div class="panel-head">Featured image carousel</div><div class="panel-body"><form method="POST" action="{{ route('superadmin.site-builder.slide.create') }}" enctype="multipart/form-data">@csrf<div class="row">@foreach(['eyebrow'=>'Eyebrow','title'=>'Title','description'=>'Description','primary_label'=>'Primary button','primary_url'=>'Primary URL','secondary_label'=>'Secondary button','secondary_url'=>'Secondary URL'] as $key=>$label)<div class="col-md-6 mb-3"><label>{{ $label }}</label><input class="form-control" name="{{ $key }}"></div>@endforeach<div class="col-md-6 mb-3"><label>Image URL</label><input class="form-control" name="image"></div><div class="col-md-12 mb-3"><label>Upload one or more slide images</label><input type="file" class="form-control-file" name="image_files[]" accept="image/png,image/jpeg,image/webp" multiple><small class="text-muted">Each selected image creates a featured slide. Use PNG, JPG or WebP.</small></div></div><label><input type="checkbox" name="is_active" checked> Active</label><button class="btn btn-success ml-2">Add slide(s)</button></form><hr>@foreach($slides as $slide)<details class="plan-row"><summary><strong>{{ $slide->title }}</strong></summary><form method="POST" action="{{ route('superadmin.site-builder.slide.update',$slide) }}" enctype="multipart/form-data" class="mt-3">@csrf<div class="row">@foreach(['eyebrow'=>'Eyebrow','title'=>'Title','description'=>'Description','primary_label'=>'Primary button','primary_url'=>'Primary URL','secondary_label'=>'Secondary button','secondary_url'=>'Secondary URL'] as $key=>$label)<div class="col-md-6 mb-2"><label>{{ $label }}</label><input class="form-control" name="{{ $key }}" value="{{ $slide->{$key} }}"></div>@endforeach<div class="col-md-12 mb-2"><label>Image upload or URL</label><input type="file" class="form-control-file mb-2" name="image_file"><input class="form-control" name="image" value="{{ $slide->image }}"></div></div><label><input type="checkbox" name="is_active" {{ $slide->is_active?'checked':'' }}> Active</label><button class="btn btn-primary ml-2">Save slide</button></form><form method="POST" action="{{ route('superadmin.site-builder.slide.delete',$slide) }}" class="mt-2">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete slide</button></form></details>@endforeach</div></section>
<section id="plans" class="panel">
    <div class="panel-head">Subscription plans</div>
    <div class="panel-body">
        @foreach($plans as $plan)
        <form method="POST" action="{{ route('superadmin.site-builder.plan.update', $plan) }}" class="plan-row">
            @csrf
            <div class="row">
                <div class="col-md-2 mb-2"><label>Name</label><input class="form-control" name="name" value="{{ $plan->name }}"></div>
                <div class="col-md-2 mb-2"><label>Monthly ₦</label><input type="number" min="0" class="form-control" name="monthly_price" value="{{ $plan->monthly_price }}"></div>
                <div class="col-md-2 mb-2"><label>Yearly ₦</label><input type="number" min="0" class="form-control" name="yearly_price" value="{{ $plan->yearly_price }}"></div>
                <div class="col-md-2 mb-2"><label>Maximum users</label><input type="number" min="1" max="10000" class="form-control" name="max_users" value="{{ $plan->max_users }}"></div>
                <div class="col-md-2 mb-2"><label>Maximum branches</label><input type="number" min="0" max="100000" class="form-control" name="max_branches" value="{{ $plan->max_branches }}" placeholder="Unlimited"><small class="text-muted">Blank means unlimited.</small></div>
                <div class="col-md-12 mb-2"><label>Description</label><textarea class="form-control" name="description">{{ $plan->description }}</textarea></div>
                <div class="col-md-12"><label>Features, one per line</label><textarea class="form-control" name="features" rows="4">{{ implode("\n", $plan->featureList()) }}</textarea></div>
            </div>
            <label class="mr-3"><input type="checkbox" name="is_featured" {{ $plan->is_featured ? 'checked' : '' }}> Featured</label>
            <label><input type="checkbox" name="is_active" {{ $plan->is_active ? 'checked' : '' }}> Active</label>
            <button class="btn btn-primary float-right">Save plan</button>
        </form>
        @endforeach
    </div>
</section>
<section id="testimonials" class="panel">
    <div class="panel-head">Testimonials carousel</div>
    <div class="panel-body">
        <div class="custom-control custom-checkbox mb-3"><input class="custom-control-input" id="testimonials-enabled" form="site-settings-form" type="checkbox" name="testimonials_enabled" value="1" {{ ($settings['testimonials_enabled'] ?? '1') === '1' ? 'checked' : '' }}><label class="custom-control-label font-weight-bold" for="testimonials-enabled">Enable testimonials on the landing page</label><small class="d-block text-muted">Save website settings to publish or hide the entire testimonials section. Individual testimonials can still be hidden below.</small></div>
        <p class="text-muted">Add up to 10 customer stories. Active testimonials rotate automatically on the public landing page. Use each item’s checkbox to hide or show it without deleting.</p>
        <p class="mb-3"><strong>{{ $testimonials->count() }} / 10 testimonials</strong></p>
        @if($errors->has('testimonial_limit'))<div class="alert alert-warning">{{ $errors->first('testimonial_limit') }}</div>@endif
        @if($testimonials->count() < 10)
        <form method="POST" action="{{ route('superadmin.site-builder.testimonial.create') }}" enctype="multipart/form-data" class="plan-row">
            @csrf
            <h3 class="h5 mb-3">Add a testimonial</h3>
            <div class="row">
                <div class="col-md-4 mb-2"><label>Name</label><input class="form-control" name="name" required></div>
                <div class="col-md-4 mb-2"><label>Role / business</label><input class="form-control" name="role"></div>
                <div class="col-md-2 mb-2"><label>Rating</label><select class="form-control" name="rating"><option value="5">5 stars</option><option value="4">4 stars</option><option value="3">3 stars</option><option value="2">2 stars</option><option value="1">1 star</option></select></div>
                <div class="col-md-2 mb-2"><label>Order</label><input type="number" min="0" class="form-control" name="sort_order" value="0"></div>
                <div class="col-md-12 mb-2"><label>Customer photo (optional)</label><input type="file" class="form-control-file" name="image_file" accept="image/png,image/jpeg,image/webp"><small class="text-muted">PNG, JPG or WebP, up to 5 MB.</small></div>
                <div class="col-md-12 mb-2"><label>Quote</label><textarea class="form-control" name="quote" rows="3" required></textarea></div>
            </div>
            <label><input type="checkbox" name="is_active" checked> Show this testimonial on the landing page</label>
            <button class="btn btn-success ml-2">Add testimonial</button>
        </form>
        @else
        <div class="alert alert-info">You have reached the 10 testimonial limit. Delete one to add another.</div>
        @endif
        @foreach($testimonials as $testimonial)
        <details class="plan-row">
            <summary><strong>{{ $testimonial->name }}</strong> <span class="text-muted">{{ $testimonial->role }}</span> @if(!$testimonial->is_active)<span class="badge badge-secondary">Hidden</span>@endif</summary>
            <form method="POST" action="{{ route('superadmin.site-builder.testimonial.update',$testimonial) }}" enctype="multipart/form-data" class="mt-3">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-2"><label>Name</label><input class="form-control" name="name" value="{{ $testimonial->name }}" required></div>
                    <div class="col-md-4 mb-2"><label>Role / business</label><input class="form-control" name="role" value="{{ $testimonial->role }}"></div>
                    <div class="col-md-2 mb-2"><label>Rating</label><select class="form-control" name="rating">@for($rating=5;$rating>=1;$rating--)<option value="{{ $rating }}" {{ $testimonial->rating===$rating?'selected':'' }}>{{ $rating }} star{{ $rating === 1 ? '' : 's' }}</option>@endfor</select></div>
                    <div class="col-md-2 mb-2"><label>Order</label><input type="number" min="0" class="form-control" name="sort_order" value="{{ $testimonial->sort_order }}"></div>
                    <div class="col-md-12 mb-2"><label>Customer photo</label><input type="file" class="form-control-file" name="image_file" accept="image/png,image/jpeg,image/webp"><small class="text-muted">Upload a new photo to replace the current one.</small>@if($testimonial->image)@php $testimonialPhotoPath = ltrim(preg_replace('#^(storage/|public/)#', '', $testimonial->image), '/'); @endphp @if(\Illuminate\Support\Facades\Storage::disk('public')->exists($testimonialPhotoPath))<img src="{{ asset('storage/'.$testimonialPhotoPath) }}" alt="Photo of {{ $testimonial->name }}" style="display:block;width:64px;height:64px;object-fit:cover;border-radius:50%;margin-top:10px">@endif @endif</div>
                    <div class="col-md-12 mb-2"><label>Quote</label><textarea class="form-control" name="quote" rows="3" required>{{ $testimonial->quote }}</textarea></div>
                </div>
                <label><input type="checkbox" name="is_active" {{ $testimonial->is_active?'checked':'' }}> Show this testimonial on the landing page</label>
                <button class="btn btn-primary ml-2">Save changes</button>
            </form>
            <form method="POST" action="{{ route('superadmin.site-builder.testimonial.delete',$testimonial) }}" class="mt-2">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove testimonial</button></form>
        </details>
        @endforeach
    </div>
</section>
</div>
</section>
@stop

@push('include-css')
<style>
.site-builder{background:#f4f7fb;min-height:calc(100vh - 66px)}.site-builder-main{margin:auto;max-width:1250px;padding:32px;width:100%}.page-title{font-size:28px;font-weight:800}.panel{background:#fff;border:1px solid #e4e7ec;border-radius:14px;margin-bottom:24px}.panel-head{padding:18px 22px;border-bottom:1px solid #eaecf0;font-weight:800}.panel-body{padding:22px}.form-control{height:44px;border-radius:8px}.upload-box{border:1px dashed #98a2b3;border-radius:10px;padding:18px;background:#fcfcfd}.plan-row{border:1px solid #eaecf0;border-radius:12px;padding:18px;margin-bottom:16px}@media(max-width:767px){.site-builder-main{padding:18px}.page-title{font-size:24px}}
</style>
@endpush
