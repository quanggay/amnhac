<?php

namespace App\Modules\MusicType\Controllers;


use App\Modules\MusicType\Models\MusicType; // Thay đổi model ở đây
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\MusicCompany\Models\MusicCompany; 

class MusicTypeController extends Controller
{
    public function index()
    {
        $active_menu = "musictype_management";
        $breadcrumb = '
            <li class="breadcrumb-item"><a href="#">/</a></li>
            <li class="breadcrumb-item active" aria-current="page">Danh sách Thể loại Âm nhạc</li>';

        $music_types = MusicType::paginate(10); // Thay đổi từ MusicCompany sang MusicType
        return view('MusicType::musictype.index', compact('music_types', 'active_menu', 'breadcrumb'));
    }

    public function create()
    {
        
        $active_menu = "musictype_management"; // Xác định trạng thái menu hiện tại
        $breadcrumb = '
            <li class="breadcrumb-item"><a href="' . route('admin.musictype.index') . '">Danh sách thể loại âm nhạc</a></li>
            <li class="breadcrumb-item active" aria-current="page">Thêm Thể Loại Âm Nhạc</li>';

        return view('MusicType::musictype.create', compact('active_menu', 'breadcrumb'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:music_types', // Kiểm tra slug là duy nhất
            'status' => 'required|string|in:active,inactive',
        ]);

        // Tạo slug tự động nếu không có
        $validatedData['slug'] = $validatedData['slug'] ?? $this->createSlug($validatedData['title']);

        // Lưu vào cơ sở dữ liệu
        MusicType::create($validatedData);

        // Chuyển hướng về danh sách thể loại âm nhạc với thông báo thành công
        return redirect()->route('admin.musictype.index')->with('success', 'Thể loại âm nhạc đã được thêm thành công.');
    }

    public function edit($id)
    {
        $active_menu = "musictype_management"; // Xác định trạng thái menu hiện tại
        $breadcrumb = '
            <li class="breadcrumb-item"><a href="' . route('admin.musictype.index') . '">Danh sách thể loại âm nhạc</a></li>
            <li class="breadcrumb-item active" aria-current="page">Điều chỉnh Thể Loại Âm Nhạc</li>';

        // Tìm thể loại âm nhạc
        $musicType = MusicType::findOrFail($id);

        return view('MusicType::musictype.edit', compact('musicType', 'active_menu', 'breadcrumb'));
    }

    public function update(Request $request, $id)
    {
        // Tìm thể loại âm nhạc theo ID
        $musicType = MusicType::findOrFail($id);

        // Xác thực dữ liệu đầu vào
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:music_types,slug,' . $musicType->id, // Kiểm tra slug là duy nhất
            'status' => 'required|string|in:active,inactive',
        ]);

        // Cập nhật slug nếu tiêu đề đã thay đổi
        if ($musicType->title !== $request->title) {
            $validatedData['slug'] = $this->createSlug($request->title);
        }

        // Cập nhật các thuộc tính khác
        $musicType->update($validatedData);

        // Chuyển hướng với thông báo thành công
        return redirect()->route('admin.musictype.index')->with('success', 'Thể loại âm nhạc đã được cập nhật thành công.');
    }

    public function destroy(string $id)
    {
        $musicType = MusicType::find($id);

        if ($musicType) {
            // Xóa bản ghi thể loại âm nhạc
            $musicType->delete();
            return redirect()->route('admin.musictype.index')->with('success', 'Xóa thể loại âm nhạc thành công!');
        } else {
            return back()->with('error', 'Không tìm thấy dữ liệu thể loại âm nhạc!');
        }
    }

    public function show($id)
    {
        $active_menu = "musictype_management";
        $breadcrumb = '
            <li class="breadcrumb-item"><a href="' . route('admin.musictype.index') . '">Danh sách thể loại âm nhạc</a></li>
            <li class="breadcrumb-item active" aria-current="page">Chi Tiết Thể Loại Âm Nhạc</li>';

        $musicType = MusicType::findOrFail($id);

        return view('MusicType::musictype.show', compact('musicType', 'active_menu', 'breadcrumb'));
    }

    protected function createSlug($title)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    }
    public function status(Request $request, $id)
{
    // Kiểm tra xem thể loại âm nhạc có tồn tại không
    $musicType = MusicType::find($id);
    if (!$musicType) {
        return response()->json(['msg' => 'Không tìm thấy thể loại âm nhạc.'], 404);
    }

    // Cập nhật trạng thái
    $musicType->status = $request->mode;
    $musicType->save();

    // Trả về phản hồi thành công
    return response()->json(['msg' => 'Cập nhật thành công.']);
}


}