<?php

namespace App\Modules\Fanclub\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fanclub\Models\Fanclub;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FanclubController extends Controller
{
    protected $pagesize;
    public function index()
    {
        $func = "fanclub_list";
        if(!$this->check_function($func))
        {
            return redirect()->route('unauthorized');
        }
        $active_menu="fanclub_list";
        $breadcrumb = '
        <li class="breadcrumb-item"><a href="#">/</a></li>
        <li class="breadcrumb-item active" aria-current="page"> Danh sách câu lạc bộ </li>';
        $fanclub=Fanclub::orderBy('id','DESC')->paginate($this->pagesize);
        return view('Fanclub::fanclub.index',compact('fanclub','breadcrumb','active_menu'));
    }

    public function create()
    {
        //
        $func = "fanclub_add";
        if(!$this->check_function($func))
        {
            return redirect()->route('unauthorized');
        }
        $active_menu="fanclub_add";
        $breadcrumb = '
        <li class="breadcrumb-item"><a href="#">/</a></li>
        <li class="breadcrumb-item  " aria-current="page"><a href="'.route('admin.Fanclub.index').'">Danh sách câu lạc bộ</a></li>
        <li class="breadcrumb-item active" aria-current="page"> Tạo câu lạc bộ người hâm mộ </li>';
        
        $fanclub=Fanclub::orderBy('id','DESC')->paginate($this->pagesize);
        return view('Fanclub::fanclub.create',compact('fanclub','breadcrumb','active_menu'));
    }

    private function uploadAvatar($file)
    {
        if ($file) {
            // Tạo tên file duy nhất
            $filename = time() . '-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/fanclub'), $filename);
            return 'uploads/fanclub/' . $filename;
        }
        return null;
    }

    public function store(Request $request)
    {
            $func = "fanclub_add";
            if(!$this->check_function($func))
            {
                return redirect()->route('unauthorized');
            }

            // Validate input
            $this->validate($request, [
                'title' => 'required|string|max:255',
                'photo' => 'nullable|string',
                'summary' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'required|in:active,inactive',
            ]);

            // Lấy tất cả dữ liệu từ form
            // dd($request->all());
            $photoUrl = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('photos', 'public');
                $photoUrl = Storage::url($photoPath);
                $photoUrl = Str::replaceFirst('http://localhost', '', $photoUrl); // Remove "http://localhost" from URL
                $validate['photo'] = $photoUrl;
            }

            $data = $request->all();
            $data['slug'] = Str::slug($request->title);
            $data['user_id'] = '1';

            $photoPaths = [];
            if ($request->hasFile('photo')) { 
                foreach ($request->file('photo') as $file) {
                    $photoPaths[] = $this->uploadAvatar($file);  // Gọi hàm upload ảnh
                }
            }

            $data['photo'] = json_encode($photoPaths);

            // Tạo một câu lạc bộ mới
            $status = Fanclub::create($data);

            if ($status) {
                return redirect()->route('admin.Fanclub.index')->with('success', 'Tạo câu lạc bộ thành công!');
            } else {
                return back()->with('error', 'Có lỗi xãy ra!');
            }
    }

    public function edit(string $id)
    {
        //
        $func = "fanclub_edit";
        if(!$this->check_function($func))
        {
            return redirect()->route('unauthorized');
        }
        $fanclub = Fanclub::find($id);
        if($fanclub)
        {
            $active_menu="fanclub_edit";
            $breadcrumb = '
            <li class="breadcrumb-item"><a href="#">/</a></li>
            <li class="breadcrumb-item  " aria-current="page"><a href="'.route('admin.Fanclub.index').'">Danh sách câu lạc bộ</a></li>
            <li class="breadcrumb-item active" aria-current="page"> Điều chỉnh câu lạc bộ </li>';
            return view('Fanclub::fanclub.edit',compact('breadcrumb','fanclub','active_menu'));
        }
        else
        {
            return back()->with('error','Không tìm thấy dữ liệu');
        }
    }

    public function update(Request $request, string $id)
    {
        $func = "fanclub_edit";
        if(!$this->check_function($func))
        {
            return redirect()->route('unauthorized');
        }
        //
        $fanclub = Fanclub::find($id);
        if($fanclub)
        {
            $this->validate($request,[
                'title' => 'required|string|max:255',
                'photo' => 'required|string',
                'summary' => 'nullable|string',
                'content' => 'nullable|string',
                'status' => 'required|in:active,inactive',
            ]);
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('photos', 'public');
                $photoUrl = Storage::url($photoPath);
                $photoUrl = Str::replaceFirst('http://localhost', '', $photoUrl); // Remove "http://localhost" from URL
                $validated['photo'] = $photoUrl;
            }

            $data = $request->all();
            $data['slug'] = Str::slug($request->title);
            $status = $fanclub->fill($data)->save();
            if($status){
                return redirect()->route('admin.Fanclub.index')->with('success','Cập nhật thành công');
            }
            else
            {
                return back()->with('error','Something went wrong!');
            }    
        }
        else
        {
            return back()->with('error','Không tìm thấy dữ liệu');
        }
      
    }

    public function destroy(string $id)
    {
        //
        $func = "fanclub_delete";
        if(!$this->check_function($func))
        {
            return redirect()->route('unauthorized');
        }
        $fanclub = Fanclub::find($id);
        if($fanclub)
        {
            $status = $fanclub->delete();
            if($status){
                return redirect()->route('admin.Fanclub.index')->with('success','Xóa thành công!');
            }
            else
            {
                return back()->with('error','Có lỗi xãy ra!');
            }    
        }
        else
        {
            return back()->with('error','Không tìm thấy dữ liệu');
        }
    }
}