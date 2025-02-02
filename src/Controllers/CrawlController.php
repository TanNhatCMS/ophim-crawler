<?php

namespace Ophim\Crawler\OphimCrawler\Controllers;


use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Ophim\Crawler\OphimCrawler\Crawler;
use Ophim\Core\Models\Movie;

/**
 * Class CrawlController
 * @package Ophim\Crawler\OphimCrawler\Controllers
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CrawlController extends CrudController
{
    protected $excludedCategories;
    protected $excludedRegions;
    protected $excludedType;

    public function fetch(Request $request)
    {
        try {
            $data = collect();
            $this->excludedCategories = request('excludedCategories', []);
            $this->excludedRegions = request('excludedRegions', []);
            $this->excludedType = request('excludedType', []);
            $request['link'] = preg_split('/[\n\r]+/', $request['link']);
            foreach ($request['link'] as $link) {
                if (preg_match('/(.*?)(\/phim\/)(.*?)/', $link)) {
                    $link = sprintf('%s/phim/%s', config('ophim_crawler.domain', 'https://ophim1.com'), explode('phim/', $link)[1]);
                    $response = json_decode(file_get_contents($link), true);
                    $data->push(collect($response['movie'])->only('name', 'slug')->toArray());
                } else {
                    for ($i = $request['from']; $i <= $request['to']; $i++) {
                        $response = json_decode(Http::timeout(30)->get($link, [
                            'page' => $i
                        ]), true);
                        if ($response['status']) {
                            //$payload = json_decode($body = file_get_contents($response['items']), true);
                            //if($this->checkIsInExcludedList($payload)){
                           //     $data->push(...$response['items']);
                            //}
                            $data->push(...$response['items']);
                        }
                    }
                }
            }

            return $data->shuffle();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function showCrawlPage(Request $request)
    {
        if (!backpack_user()->hasPermissionTo('Browse crawl schedule')) {
            abort(403);
        }
        $categories = [];
        $regions = [];
        try {
            $categories = Cache::remember('ophim_categories', 86400, function () {
                $data = json_decode(file_get_contents(sprintf('%s/the-loai', config('ophim_crawler.domain', 'https://ophim1.com'))), true) ?? [];
                return collect($data)->pluck('name', 'name')->toArray();
            });

            $regions = Cache::remember('ophim_regions', 86400, function () {
                $data = json_decode(file_get_contents(sprintf('%s/quoc-gia', config('ophim_crawler.domain', 'https://ophim1.com'))), true) ?? [];
                return collect($data)->pluck('name', 'name')->toArray();
            });
        } catch (\Throwable $th) {}
        $fields = $this->movieUpdateOptions();

        return view('ophim-crawler::crawl', compact('fields', 'regions', 'categories'));
    }

    public function crawl(Request $request)
    {

        $pattern = sprintf('%s/phim/{slug}', config('ophim_crawler.domain', 'https://ophim1.com'));
        try {
            $link = str_replace('{slug}', $request['slug'], $pattern);
            $crawler = (new Crawler($link, request('fields', []), request('excludedCategories', []), request('excludedRegions', []), request('excludedType', []), request('forceUpdate', false)))->handle();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'wait' => false], 500);
        }
        return response()->json(['message' => 'Xong', 'wait' => $crawler ?? true]);
    }

    protected function movieUpdateOptions(): array
    {
        return [
            'Tiến độ phim' => [
                'episodes' => 'Tập mới',
                'status' => 'Trạng thái phim',
                'episode_time' => 'Thời lượng tập phim',
                'episode_current' => 'Số tập phim hiện tại',
                'episode_total' => 'Tổng số tập phim',
            ],
            'Thông tin phim' => [
                'name' => 'Tên phim',
                'origin_name' => 'Tên gốc phim',
                'content' => 'Mô tả nội dung phim',
                'thumb_url' => 'Ảnh Thumb',
                'poster_url' => 'Ảnh Poster',
                'trailer_url' => 'Trailer URL',
                'quality' => 'Chất lượng phim',
                'language' => 'Ngôn ngữ',
                'notify' => 'Nội dung thông báo',
                'showtimes' => 'Giờ chiếu phim',
                'publish_year' => 'Năm xuất bản',
                'is_copyright' => 'Đánh dấu có bản quyền',
            ],
            'Phân loại' => [
                'type' => 'Định dạng phim',
                'is_shown_in_theater' => 'Đánh dấu phim chiếu rạp',
                'actors' => 'Diễn viên',
                'directors' => 'Đạo diễn',
                'categories' => 'Thể loại',
                'regions' => 'Khu vực',
                'tags' => 'Từ khóa',
                'studios' => 'Studio',
            ],
            'Cập nhật' => [
                'updated_at' => 'Cập nhật thời gian',
            ],
        ];
    }
    public function getMoviesFromParams(Request $request) {
        $field = explode('-', request('params'))[0];
        $val = explode('-', request('params'))[1];
        if (!$val) {
            return Movie::where($field, $val)->orWhere($field, 'like', '%.com%')->orWhere($field, NULL)->get();
        } else {
            return Movie::where($field, $val)->get();
        }
    }
    protected function checkIsInExcludedList($payload)
    {
        $newType = $payload['movie']['type'];
        if (in_array($newType, $this->excludedType)) {
            return false;
        }
        $newCategories = collect($payload['movie']['category'])->pluck('name')->toArray();
        if (array_intersect($newCategories, $this->excludedCategories)) {
            return false;
        }
        $newRegions = collect($payload['movie']['country'])->pluck('name')->toArray();
        if (array_intersect($newRegions, $this->excludedRegions)) {
            return false;
        }
        return true;
    }

}