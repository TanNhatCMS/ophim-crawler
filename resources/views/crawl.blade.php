@extends(backpack_view('blank'))

@php
$defaultBreadcrumbs = [
    trans('backpack::crud.admin') => backpack_url('dashboard'),
    'Crawler' => backpack_url('plugin/ophim-crawler'),
];

$breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid">
        <h2>
            <span class="text-capitalize">Movies</span>
            <small>Crawler</small>
        </h2>
    </section>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 steps">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        @csrf
                        <div class="form-group col-12 mb-3">
                            <label for="">Link</label>
                            <textarea class="form-control" rows="5" name="link">https://ophim1.com/danh-sach/phim-moi-cap-nhat</textarea>
                            <small><i>Mỗi link cách nhau 1 dòng</i></small>
                        </div>
                        <div class="form-group col-12">
                            <label class="text-danger">Loại trừ định dạng</label>
                            <button id="excluded-all-type" type="button" class="btn btn-sm btn-info">All</button>
                            <select id="excluded-type" class="form-control select2" name="excludedType[]" multiple>
                                <option value="series">Phim Bộ</option>
                                <option value="single">Phim Lẻ</option>
                                <option value="hoathinh">Hoạt Hình</option>
                                <option value="tvshows">TV Shows</option>
                            </select>
                        </div>
                        <div class="form-group col-12">
                            <label class="text-danger">Loại trừ thể loại</label>
                            <button id="excluded-all-category" type="button" class="btn btn-sm btn-info">All</button>
                            <select id="excluded-category" class="form-control select2" name="excludedCategories[]" multiple>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-12">
                            <label class="text-danger">Loại trừ quốc gia</label>
                            <button id="excluded-all-regions" type="button" class="btn btn-sm btn-info">All</button>
                            <select id="excluded-regions" class="form-control select2" name="excludedRegions[]" multiple>
                                @foreach ($regions as $region)
                                    <option value="{{ $region }}">{{ $region }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label>Từ page</label>
                            <input type="number" class="form-control" name="from" min="0" value="1">
                        </div>
                        <div class="form-group col-6">
                            <label>Tới page</label>
                            <input type="number" class="form-control" name="to" min="0" value="1">
                        </div>
                        <div class="form-group col-6">
                            <button class="btn btn-primary btn-load">Tải</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 steps d-none">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-12">
                            <h4>Chọn phim</h4>
                            <p>Đã chọn <span class="selected-movie-count">0</span>/<span class="total-movie-count">0</span>
                                phim</p>
                            <div class="form-group">
                                <input type="checkbox" id="check-all" checked>
                                <label class="form-check-label" for="check-all">Check All</label>
                            </div>
                            <div class="row px-3 my-3">
                                <div class="w-100 col-form-label overflow-auto" id="movie-list"
                                    style="height: 20rem;background-color: #f7f7f7">

                                </div>
                            </div>
                            <button class="btn btn-secondary btn-previous">Trước</button>
                            <button class="btn btn-primary btn-next">Tiếp</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8 steps d-none">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mx-3">
                            @foreach ($fields ?? [] as $groupLabel => $options)
                                <div class="row mb-3">
                                    <div class="col-12 px-0">
                                        <input class="group-checkall"
                                            data-target="{{ Str::slug($groupLabel) }}-group-checkbox"
                                            id="{{ Str::slug($groupLabel) }}-check-all" type="checkbox">
                                        <label for="{{ Str::slug($groupLabel) }}-check-all">{{ $groupLabel }}</label>
                                    </div>
                                    @foreach ($options as $key => $option)
                                        <div class="col-12 col-md-6 form-check checkbox">
                                            <input class="form-check-input {{ Str::slug($groupLabel) }}-group-checkbox"
                                                id="{{ Str::slug($key) }}-{{ $loop->index }}" type="checkbox"
                                                name="fields[]" value="{{ $key }}" checked>
                                            <label class="d-inline" for="{{ Str::slug($key) }}-{{ $loop->index }}">
                                                {{ $option }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <button class="btn btn-secondary btn-previous">Trước</button>
                    <button class="btn btn-primary btn-process">Tiếp</button>
                </div>
            </div>
        </div>

        <div class="col-md-8 steps d-none">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-12">
                            <h4>Đang tiến hành...</h4>
                            <p>Crawl <span class="crawled-count">0</span>/<span class="total-crawl-count">0</span>
                                phim (Thành công: <span class="crawl-success-count">0</span>, thất bại: <span
                                    class="crawl-failed-count">0</span>).</p>
                            <div class="form-group row">
                                <div class="w-100 px-3 col-form-label overflow-auto mb-2 mx-3" id="crawl-list"
                                    style="height: 20rem;background-color: #f7f7f7">

                                </div>
                                <div class="w-100 px-3 col-form-label overflow-auto mx-3" id="logs"
                                    style="height: 5rem;background-color: #f7f7f7">

                                </div>
                            </div>
                            <button class="btn btn-secondary btn-cancel btn-previous">Trước</button>
                            <button class="btn btn-primary">Xong</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="{{ asset('/packages/select2/dist/css/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('/packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
        integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ asset('/packages/select2/dist/js/select2.full.min.js') }}"></script>

    @push('after_scripts')
        <script src="{{ asset('/packages/select2/dist/js/select2.full.min.js') }}"></script>
        <script>
            $(function() {
                $(".select2").select2();

                $("#excluded-all-type").on("click", function () {
                    var allType = [];
                    if($("#excluded-type").val().length === 0) allType = ['series', 'single', 'hoathinh', 'tvshows'];
                    $("#excluded-type").val(allType).trigger("change");
                });

                $("#excluded-all-category").on("click", function () {
                    var allCategory = [];
                    if($("#excluded-category").val().length === 0) allCategory = @json($categories);
                    $("#excluded-category").val(Object.values(allCategory)).trigger("change");
                });

                $("#excluded-all-regions").on("click", function () {
                    var allRegions = [];
                    if($("#excluded-regions").val().length === 0) allRegions = @json($regions);
                    $("#excluded-regions").val(Object.values(allRegions)).trigger("change");
                });
            })
        </script>
        <script>
            $(document).ready(function() {
                $("input[name=from]").val(localStorage.getItem('crawler-page-from') ?? 1);
                $("input[name=to]").val(localStorage.getItem('crawler-page-to') ?? 1);
                $("#excluded-type").val(localStorage.getItem('crawler-excluded-type')?.split(",") ?? []).trigger("change");
                $("#excluded-category").val(localStorage.getItem('crawler-excluded-category')?.split(",") ?? []).trigger("change");
                $("#excluded-regions").val(localStorage.getItem('crawler-excluded-regions')?.split(",") ?? []).trigger("change");
                $("#excluded-category").on('change', () => {
                    localStorage.setItem('crawler-excluded-category', $("#excluded-category").val());
                });
                $("#excluded-type").on('change', () => {
                    localStorage.setItem('crawler-excluded-type', $("#excluded-type").val());
                });
                $("#excluded-regions").on('change', () => {
                    localStorage.setItem('crawler-excluded-regions', $("#excluded-regions").val());
                });
                $("input[name=from]").on('change', () => {
                    localStorage.setItem('crawler-page-from', $("input[name=from]").val());
                });
                $("input[name=to]").on('change', () => {
                    localStorage.setItem('crawler-page-to', $("input[name=to]").val());
                });
            });
        </script>
    @endpush
    <script>
        var isFetching = false;
        $('.btn-load').click(function(e) {
            if (isFetching) return;
            const btn = $(this);
            const link = $('textarea[name="link"]').val();
            const from = $('input[name="from"]').val();
            const to = $('input[name="to"]').val();

            if (!link) {
                $('textarea[name="link"]').addClass('is-invalid');
                return;
            }
            $('textarea[name="link"]').removeClass('is-invalid');
            $('.btn-load').html('Đang tải...');

            const fetchApi = async (link, from, to) => {
                isFetching = true;
                const response = await fetch("{{ backpack_url('plugin/ophim-crawler/fetch') }}?" +
                    new URLSearchParams({
                        link,
                        from,
                        to
                    }));

                if (response.status >= 200 && response.status < 300) {
                    return {
                        response: response,
                        payload: await response.json()
                    }
                }

                throw {
                    response
                }
            }

            fetchApi(link, from, to).then(res => {
                if (res.payload.length > 0) {
                    const template = (data) => {
                        let html = '';
                        data.forEach((item, i) => {
                            html += `<div class="form-check checkbox">
                                        <input class="movie-checkbox" id="movie-${i}" type="checkbox" value="${encodeURI(JSON.stringify(item))}" checked>
                                        <label class="d-inline" for="movie-${i}">${item.name}</label>
                                    </div>`;
                        })
                        return html;
                    }

                    $('.total-movie-count').html(res.payload.length)
                    $('.selected-movie-count').html(res.payload.length)
                    $('#movie-list').html(template(res.payload))

                    next(this)
                }
            }).catch(err => {
                $('input[name="link"]').addClass('is-invalid');
            }).finally(() => {
                $('.btn-load').html('Tải');
                isFetching = false;
            })
        })

        $('.btn-process').click(function() {
            const values = $(".movie-checkbox:checked")
                .map(function() {
                    return JSON.parse(decodeURI($(this).val()));
                }).get();

            const template = (data) => {
                let html = '';
                data.forEach((item, i) => {
                    html +=
                        `<p class="crawling-movie text-muted d-flex justify-content-between" data-slug="${item.slug}">
                            <span>${i+1}. ${item.name}</span>
                            <span class="status">Pending</span>
                        </p>`
                })
                return html;
            }

            $('.total-crawl-count').html(values.length);
            $('#crawl-list').html(template(values));

            crawl($('.crawling-movie').first())

            next(this);
        })

        $('.btn-next').click(function() {
            next(this);
        })

        $('.btn-previous').click(function() {
            prev(this);
        })

        $('#check-all').change(function() {
            $('.movie-checkbox').prop('checked', $(this).prop('checked'))
            $('.selected-movie-count').html($('.movie-checkbox:checked').length)
        })

        $(document).on('change', '.movie-checkbox', function() {
            $('.selected-movie-count').html($('.movie-checkbox:checked').length)
        })

        $('.group-checkall').change(function() {
            $(`.${$(this).data('target')}`).prop('checked', this.checked);
        })

        const next = (el) => {
            $('.steps').addClass('d-none');
            $(el).closest('.steps').next().removeClass('d-none');
        }

        const prev = (el) => {
            $('.steps').addClass('d-none');
            $(el).closest('.steps').prev().removeClass('d-none');
        }

        const crawl = (el) => {
            const slug = $(el).data('slug');

            if (!slug) return;
            processMovie(slug).then(res => {
                $(`.crawling-movie[data-slug="${slug}"]`).removeClass('text-info');
                $(`.crawling-movie[data-slug="${slug}"]`).addClass('text-success');
                $(`.crawling-movie[data-slug="${slug}"] .status`).html('OK');
                $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-success');
            }).catch(err => {
                $(`.crawling-movie[data-slug="${slug}"]`).removeClass('text-info');
                $(`.crawling-movie[data-slug="${slug}"]`).addClass('text-danger');
                $(`.crawling-movie[data-slug="${slug}"] .status`).html('Error');
                $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-failed');
                $(`#logs`).append(
                    `<li class="text-danger">${slug} : ${err?.payload?.message ?? 'Unknown error'}</li>`);
            }).finally(() => {
                $(`.crawling-movie[data-slug="${slug}"]`).addClass('crawl-completed');
                $('.crawled-count').html($('.crawl-completed').length)
                $('.crawl-success-count').html($('.crawl-success').length)
                $('.crawl-failed-count').html($('.crawl-failed').length)
                crawl($(el).next('.crawling-movie'))
            })
        }

        const processMovie = async (slug) => {
            $(`.crawling-movie[data-slug="${slug}"]`).removeClass('text-muted');
            $(`.crawling-movie[data-slug="${slug}"]`).addClass('text-info');
            $(`.crawling-movie[data-slug="${slug}"] .status`).html('Processing');

            const fields = $("input[name='fields[]']:checked")
                .map(function() {
                    return $(this).val();
                }).get();

            const excludedCategories = $("[name='excludedCategories[]']").val()
            const excludedRegions = $("[name='excludedRegions[]']").val()
            const excludedType = $("[name='excludedType[]']").val()

            const response = await fetch("{{ backpack_url('plugin/ophim-crawler/crawl') }}", {
                method: 'POST',
                headers: {
                    "Content-Type": "application/json",
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({
                    slug,
                    fields,
                    excludedCategories,
                    excludedRegions,
                    excludedType
                })
            });

            if (response.status >= 200 && response.status < 300) {
                return {
                    response: response,
                    payload: await response.json()
                }
            }

            throw {
                response: response,
                payload: await response.json()
            }
        }
    </script>
@endsection
