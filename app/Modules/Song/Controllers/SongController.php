<?php

namespace App\Modules\Song\Controllers;
use Illuminate\Support\Facades\Auth;
use App\Modules\Song\Models\Song;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Modules\Resource\Models\Resource;
use App\Modules\Tag\Models\Tag;
use Illuminate\Support\Str;
use App\Modules\Composer\Models\Composer;
use App\Modules\Singer\Models\Singer;
use Illuminate\Support\Facades\Log;


class SongController extends Controller
{
    public function index()
    {
        $active_menu = "song_management";
        $breadcrumb = '
            <li class="breadcrumb-item"><a href="#">/</a></li>
            <li class="breadcrumb-item active" aria-current="page">Danh sách Bài Hát</li>'; 
        $songs = Song::with('resources')->paginate(10);
        $allResources = Resource::all();

        return view('Song::song.index', compact('songs', 'active_menu', 'allResources', 'breadcrumb'));
    }

    public function create()
    {
        $singers = Singer::all();
        $composers = Composer::all();
        $tags = Tag::where('status', 'active')->orderBy('title', 'ASC')->get();
        $active_menu = "song_management";
        $breadcrumb = '
             <li class="breadcrumb-item"><a href="' . route('admin.song.index') . '">Danh sách bài hát</a></li>
            <li class="breadcrumb-item active" aria-current="page">Thêm Bài Hát</li>';
        $resources = Resource::all();
        return view('Song::song.create', compact('singers','composers','resources','active_menu', 'breadcrumb', 'tags'));
    }

    public function store(Request $request)
    {
        // Validate input
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'new_tags' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'composer_id' => 'required|exists:composers,id',
            'singer_id' => 'required|exists:singers,id',
            'resources' => 'nullable|array',
        ]);
    
        // Khởi tạo mảng tagsArray từ tags đã chọn
        $tagsArray = $request->tags ?? [];
    
        // Xử lý tag mới nhập vào (nếu có)
        if ($request->new_tags) {
            $newTags = explode(',', $request->new_tags);
    
            foreach ($newTags as $newTag) {
                $newTag = trim($newTag);
                if (!empty($newTag)) {
                    $tag = Tag::firstOrCreate(['title' => $newTag]);
                    $tagsArray[] = $tag->id;
                }
            }
        }
    
        // Làm tương tự cho summary và content
        $validatedData['summary'] = strip_tags($validatedData['summary'], '<p>');
        $validatedData['summary'] = nl2br($validatedData['summary']);
    
        $validatedData['content'] = strip_tags($validatedData['content'], '<p>');
        $validatedData['content'] = nl2br($validatedData['content']);
    
        // Tạo mới bài hát
        $song = new Song($validatedData);
        $song->slug = $this->createSlug($song->title);
        $song->status = $validatedData['status'];
        $song->tags = json_encode($tagsArray);
        $song->save();
    
        $songId = $song->id;
    
        // Mảng lưu trữ các tài nguyên
        $resourcesArray = [];
    
        // Xử lý tài nguyên và lưu vào bảng resources
        if ($request->has('resources') && is_array($request->resources)) {
            foreach ($request->resources as $resource) {
                $Code = Str::random(8);
    
                if (filter_var($resource, FILTER_VALIDATE_URL)) {
                    $resourceUrl = $resource;
                    $slug = Str::slug($song->title) . '-' . Str::random(6);
                    $fileType = 'URL';
                    $linkCode = '';
    
                    // Kiểm tra nếu URL là video YouTube
                    if (strpos($resourceUrl, 'youtube.com') !== false || strpos($resourceUrl, 'youtu.be') !== false) {
                        $linkCode = 'youtube';
                        $typeCode = 'youtube'; // Type code và link code cho YouTube
                    }
                    // Kiểm tra nếu URL là mp3
                    elseif (strpos($resourceUrl, '.mp3') !== false) {
                        $linkCode = 'audio';
                        $typeCode = 'audio';
                    }
                    // Kiểm tra nếu URL là PDF
                    elseif (strpos($resourceUrl, '.pdf') !== false) {
                        $linkCode = 'document';
                        $typeCode = 'document';
                    }
                    // Kiểm tra nếu URL là hình ảnh
                    elseif (preg_match('/\.(jpg|jpeg|png|gif)$/i', $resourceUrl)) {
                        $linkCode = 'image';
                        $typeCode = 'image';
                    }
    
                    // Lưu tài nguyên vào bảng resources
                    $resourceRecord = Resource::create([
                        'song_id' => $songId,
                        'title' => $song->title,
                        'slug' => $slug,
                        'url' => $resourceUrl,
                        'file_type' => $fileType,
                        'type_code' => $typeCode,
                        'file_name' => basename($resourceUrl),
                        'file_size' => 0,
                        'code' => 'songs',
                        'link_code' => $linkCode,
                        
                    ]);
    
                    $resourcesArray[] = [
                        'song_id' => $songId,
                        'resource_id' => $resourceRecord->id,
                    ];
    
                } elseif ($resource instanceof \Illuminate\Http\UploadedFile) {
                    // Nếu là file tải lên
                    $resourcePath = $resource->store('uploads/resources', 'public');
                    $resourceUrl = Storage::url($resourcePath);
                    $resourceUrl = Str::replaceFirst('http://localhost', '', $resourceUrl);
    
                    $slug = Str::slug($song->title) . '-' . Str::random(6);
                    $fileType = $resource->getMimeType();
                    $linkCode = '';
                    $typeCode = '';
    
                    // Xử lý các loại MIME type
                    if (strpos($fileType, 'image') !== false) {
                        $linkCode = 'image';
                        $typeCode = 'image';
                    } elseif (strpos($fileType, 'audio') !== false) {
                        $linkCode = 'audio';
                        $typeCode = 'audio';
                    } elseif (strpos($fileType, 'video') !== false) {
                        $linkCode = 'video';
                        $typeCode = 'video';
                    } elseif (strpos($fileType, 'pdf') !== false) {
                        $linkCode = 'document';
                        $typeCode = 'document';
                    }
    
                    // Lưu tài nguyên vào bảng resources
                    $resourceRecord = Resource::create([
                        'song_id' => $songId,
                        'title' => $song->title,
                        'slug' => $slug,
                        'url' => $resourceUrl,
                        'file_type' => $fileType,
                        'type_code' => $typeCode,
                        'file_name' => $resource->getClientOriginalName(),
                        'file_size' => $resource->getSize(),
                        'code' => 'songs',
                        'link_code' => $linkCode,
                        
                    ]);
    
                    $resourcesArray[] = [
                        'song_id' => $songId,
                        'resource_id' => $resourceRecord->id,
                    ];
                }
            }
        }
    
        // Lưu mảng tài nguyên vào Song
        if (!empty($resourcesArray)) {
            $song->resources = json_encode($resourcesArray);
            $song->save();
        }
    
        return redirect()->route('admin.song.index')->with('success', 'Bài hát đã được thêm thành công.');
    }
    



    // Helper method for creating slugs

    public function edit($id)
{
    
    // Lấy danh sách các thẻ (tags) có trạng thái "active"
    $tags = Tag::where('status', 'active')->orderBy('title', 'ASC')->get();
    // Thiết lập menu và breadcrumb
    $active_menu = "song_management";
    $breadcrumb = '
        <li class="breadcrumb-item"><a href="' . route('admin.song.index') . '">Danh sách bài hát</a></li>
        <li class="breadcrumb-item active" aria-current="page">Điều chỉnh bài hát</li>';
    // Lấy thông tin bài hát
    $song = Song::findOrFail($id);
    // Lấy danh sách nhạc sĩ và ca sĩ để chọn trong dropdown
    $composers = Composer::all();
    $singers = Singer::all();
    // Giải mã cột resources nếu có dữ liệu
    $resourcesArray = is_string($song->resources) ? json_decode($song->resources, true) : $song->resources;

    // Lấy tất cả các resource_id từ mảng resources đã gắn vào bài hát
    $resourceIds = array_column($resourcesArray, 'resource_id');
    
    // Nếu có resource_id, lấy danh sách tài nguyên đã gắn cho bài hát
    $resources = Resource::whereIn('id', $resourceIds)->get();

    // Lấy danh sách tài nguyên có thể thêm vào (tức là chưa được gắn)
    $availableResources = Resource::whereNotIn('id', $resourceIds)->get();

    // Giải mã cột tags nếu có dữ liệu
    $attachedTags = json_decode($song->tags, true) ?? []; // Mặc định là mảng rỗng nếu không có dữ liệu

    // Lấy tất cả các tài nguyên (resources) có thể gán vào bài hát
    $allResources = Resource::all();  // Lấy tất cả tài nguyên, có thể thêm điều kiện lọc nếu cần

    // Trả về view chỉnh sửa với các thông tin cần thiết
    return view('Song::song.edit', compact(
        'song', 'composers', 'singers','resources', 'availableResources', 'tags', 'attachedTags', 'allResources', 'active_menu', 'breadcrumb'
    ));
}

public function update(Request $request, $id)
{
    // Xác thực dữ liệu đầu vào
    $validatedData = $request->validate([
        'title' => 'required|string|max:255',
        'summary' => 'nullable|string',
        'content' => 'nullable|string',
        'tags' => 'nullable|array',
        'tags.*' => 'exists:tags,id',
        'status' => 'required|in:active,inactive',
        'composer_id' => 'required|exists:composers,id',
        'singer_id' => 'required|exists:singers,id',
        'resources' => 'nullable|array', // Tài nguyên có thể thêm hoặc không
    ]);

    // Lấy bài hát cần cập nhật
    $song = Song::findOrFail($id);

    // Lưu giá trị tài nguyên cũ (nếu có)
    $currentResources = $song->resources ? json_decode($song->resources, true) : [];

    // Khởi tạo mảng tags từ dữ liệu người dùng
    $tagsArray = $request->tags ?? [];

    // Xử lý các tag mới nếu có
    if ($request->new_tags) {
        $newTags = explode(',', $request->new_tags);

        foreach ($newTags as $newTag) {
            $newTag = trim($newTag);
            if (!empty($newTag)) {
                $tag = Tag::firstOrCreate(['title' => $newTag]);
                $tagsArray[] = $tag->id;
            }
        }
    }

    // Loại bỏ thẻ HTML và thay thế ngắt dòng trong nội dung
    $validatedData['summary'] = strip_tags($validatedData['summary'], '<p>');
    $validatedData['summary'] = nl2br($validatedData['summary']);

    $validatedData['content'] = strip_tags($validatedData['content'], '<p>');
    $validatedData['content'] = nl2br($validatedData['content']);

    // Cập nhật bài hát với dữ liệu mới
    $song->fill($validatedData);
    $song->slug = $this->createSlug($song->title);
    $song->status = $validatedData['status'];
    $song->tags = json_encode($tagsArray);

    // Kiểm tra xem người dùng có thay đổi hoặc thêm tài nguyên không
    if ($request->has('resources') && count($request->resources) > 0) {
        // Xử lý tài nguyên mới (file hoặc URL)
        $newResources = [];

        foreach ($request->resources as $resource) {
            if (filter_var($resource, FILTER_VALIDATE_URL)) {
                // Nếu là URL, xử lý tài nguyên là URL
                $slug = Str::slug($song->title) . '-' . Str::random(6);
                $resourceUrl = $resource;
                $fileType = 'URL';
                $linkCode = 'youtube'; // Đặt link_code là youtube
                $typeCode = 'youtube'; // Đặt type_code là youtube

                // Lưu tài nguyên vào cơ sở dữ liệu
                $resourceRecord = Resource::create([
                    'song_id' => $song->id,
                    'title' => $song->title,
                    'slug' => $slug,
                    'url' => $resourceUrl,
                    'file_type' => $fileType,
                    'type_code' => $typeCode,
                    'file_name' => basename($resourceUrl),
                    'file_size' => 0,
                    'code' => 'songs',
                    'link_code' => $linkCode,
                ]);

                $newResources[] = [
                    'song_id' => $song->id,
                    'resource_id' => $resourceRecord->id,
                ];
            } elseif ($resource instanceof \Illuminate\Http\UploadedFile) {
                // Nếu là tệp tải lên, xử lý tài nguyên tải lên
                $resourcePath = $resource->store('uploads/resources', 'public');
                $resourceUrl = 'http://127.0.0.1:8000/storage/' . str_replace('public/', '', $resourcePath);


                $slug = Str::slug($song->title) . '-' . Str::random(6);
                $fileType = $resource->getMimeType();
                $linkCode = ''; // Ban đầu không xác định
                $typeCode = ''; // Ban đầu không xác định

                // Kiểm tra và phân loại tệp tải lên
                if (strpos($fileType, 'image') !== false) {
                    $linkCode = 'image';
                    $typeCode = 'image';
                } elseif (strpos($fileType, 'audio') !== false) {
                    $linkCode = 'audio';
                    $typeCode = 'audio';
                } elseif (strpos($fileType, 'video') !== false) {
                    $linkCode = 'video';
                    $typeCode = 'video';
                } elseif (strpos($fileType, 'pdf') !== false) {
                    $linkCode = 'document';
                    $typeCode = 'document';
                }

                // Lưu tài nguyên vào cơ sở dữ liệu
                $resourceRecord = Resource::create([
                    'song_id' => $song->id,
                    'title' => $song->title,
                    'slug' => $slug,
                    'url' => $resourceUrl,
                    'file_type' => $fileType,
                    'type_code' => $typeCode,
                    'file_name' => $resource->getClientOriginalName(),
                    'file_size' => $resource->getSize(),
                    'code' => 'songs',
                    'link_code' => $linkCode,
                ]);

                $newResources[] = [
                    'song_id' => $song->id,
                    'resource_id' => $resourceRecord->id,
                ];
            }
        }

        // Kết hợp tài nguyên cũ và tài nguyên mới
        $combinedResources = array_merge($currentResources, $newResources);

        // Cập nhật tài nguyên mới vào cơ sở dữ liệu
        $song->resources = json_encode($combinedResources);
    } else {
        // Nếu không có tài nguyên mới, giữ nguyên tài nguyên cũ
        $song->resources = json_encode($currentResources);
    }

    // Lưu bài hát đã cập nhật
    $song->save();

    return redirect()->route('admin.song.index')->with('success', 'Bài hát đã được cập nhật thành công.');
}


    public function destroy(string $id)
{
    $song = Song::find($id);
    
    if ($song) {
        // Xóa ảnh nếu có
        if ($song->photo && $song->photo !== 'backend/images/profile-6.jpg') {
            Storage::disk('public')->delete($song->photo);
        }
    
        // Kiểm tra và xóa tài nguyên nếu có (dựa vào cột 'resources' là JSON)
        if ($song->resources) {
            $resourcesArray = json_decode($song->resources, true);  // Chuyển cột JSON thành mảng PHP
    
            // Lấy ra tất cả resource_ids từ mảng JSON
            foreach ($resourcesArray as $resource) {
                // Lấy resource_id
                $resourceId = $resource['resource_id'];
    
                // Xóa tài nguyên khỏi bảng 'resources'
                $resourceRecord = Resource::find($resourceId);
                if ($resourceRecord) {
                    // Xóa tệp tin nếu tồn tại
                    if (Storage::disk('public')->exists($resourceRecord->url)) {
                        Storage::disk('public')->delete($resourceRecord->url);
                    }

                      // Kiểm tra và xóa các tag liên kết nếu có
            if ($song->tags) {
                $tagsArray = json_decode($song->tags, true);  // Chuyển tags thành mảng
    
                if (is_array($tagsArray)) {
                    foreach ($tagsArray as $tagId) {
                        // Kiểm tra xem tag có còn được sử dụng bởi bất kỳ song nào khác không
                        $isTagInUse = song::where('tags', 'like', '%"'.$tagId.'"%')->where('id', '!=', $song->id)->exists();
    
                        // Nếu tag không còn được sử dụng, xóa nó khỏi bảng tags
                        if (!$isTagInUse) {
                            Tag::where('id', $tagId)->delete();
                        }
                    }
                }
            }
    
                    // Xóa bản ghi tài nguyên
                    $resourceRecord->delete();
                }
            }
        }
    
        // Xóa bản ghi bài hát
        $song->delete();
    
        return redirect()->route('admin.song.index')->with('success', 'Xóa bài hát và tài nguyên thành công!');
    }
    
    return back()->with('error', 'Không tìm thấy bài hát!');
    }


    
    public function show($id)
{
    // Lấy bài hát theo ID
    $song = Song::findOrFail($id);
;
    // Kiểm tra nếu 'resources' không rỗng và là một chuỗi JSON hợp lệ
    $resourcesArray = [];
    if ($song->resources) {
        $resourcesArray = json_decode($song->resources, true);
    }

    // Lấy tất cả các resource_id từ chuỗi resources
    $resourceIds = array_column($resourcesArray, 'resource_id');
    
    // Truy vấn bảng Resource để lấy các tài nguyên liên quan nếu có
    $resources = Resource::whereIn('id', $resourceIds)->get();
    
    // Cấu hình menu và breadcrumb
    $active_menu = "song_list";
    $breadcrumb = '
        <li class="breadcrumb-item"><a href="' . route('admin.song.index') . '">Danh sách bài hát</a></li>
        <li class="breadcrumb-item active" aria-current="page">Chi tiết Bài hát</li>';
    
    // Trả về view với các dữ liệu cần thiết
    return view('Song::song.show', compact('song', 'resources', 'active_menu', 'breadcrumb'));
    }
    public function createSlug($title)
{
    return Str::slug($title);
}
public function status(Request $request, $id)
{
    // Kiểm tra bài hát có tồn tại không
    $song = Song::find($id);
    if (!$song) {
        return response()->json(['msg' => 'Không tìm thấy bài hát.'], 404);
    }

    // Cập nhật trạng thái
    $song->status = $request->mode;
    $song->save();

    // Trả về phản hồi thành công
    return response()->json(['msg' => 'Cập nhật thành công.']);
}
public function deleteResource(Request $request)
{
    $song = Song::find($request->input('song_id'));

    if ($song) {
        // Tìm tài nguyên trong bảng 'resources'
        $resource = Resource::find($request->input('resource_id'));
        
        if ($resource) {
            // Xóa tệp tin nếu tồn tại
            if (Storage::disk('public')->exists($resource->url)) {
                Storage::disk('public')->delete($resource->url);
            }

            // Xóa tài nguyên khỏi bảng resources
            $resource->delete();

            // Cập nhật trường resources trong bảng songs
            $resources = json_decode($song->resources, true);

            // Lọc bỏ tài nguyên đã xóa
            $resources = array_filter($resources, function($item) use ($request) {
                return $item['resource_id'] != $request->input('resource_id');
            });

            // Cập nhật lại cột resources
            $song->resources = json_encode(array_values($resources));
            $song->save();

            // Trả về thông báo thành công
            return response()->json([
                'success' => true,
                'message' => 'Tài nguyên đã được xóa thành công.'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Tài nguyên không tồn tại.'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Bài hát không tồn tại.'
    ]);
}




}