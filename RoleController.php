<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleCreateRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Interfaces\PermissionRepositoryInterface;
use App\Interfaces\RoleRepositoryInterface;
use DataTables;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private $roleRepository;
    private $permissionRepository;

    const ROLE_INDEX_PATH = 'admin.user-management.roles.index';   // to remove duplicity of index route or view

    public function __construct(RoleRepositoryInterface $roleRepository, PermissionRepositoryInterface $permissionRepository)
    {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $roles = $this->roleRepository->getAllRoles();
        if ($request->ajax()) {
            return Datatables::of($roles)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return
                    '<ul class="data-table-list list-group list-group-horizontal gap-3 justify-content-between">
                        <li class="data-table-list-item list-group-item border-0 p-0 bg-transparent">
                            <a href="' . route('admin.user-management.roles.show', $row->id) . '" class="edit d-flex align-items-center gap-2 text-dark">
                                <svg width="18" height="14" viewBox="0 0 18 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8.81641 13.1248C7.3813 13.1426 5.96102 12.8329 4.66366 12.2191C3.65801 11.7284 2.75498 11.0508 2.00278 10.2224C1.20603 9.36586 0.57869 8.3662 0.153906 7.27625L0.0664062 6.99975L0.158281 6.72325C0.58337 5.63426 1.20936 4.63486 2.00366 3.77713C2.75558 2.94878 3.65831 2.27113 4.66366 1.78038C5.96103 1.16664 7.3813 0.856912 8.81641 0.874753C10.2515 0.856943 11.6718 1.16667 12.9692 1.78038C13.9748 2.27102 14.8779 2.94867 15.63 3.77713C16.4283 4.63249 17.0558 5.63245 17.4789 6.72325L17.5664 6.99975L17.4745 7.27625C16.1018 10.8496 12.6438 13.1854 8.81641 13.1248ZM8.81641 2.62475C5.83779 2.53142 3.1039 4.26546 1.91878 6.99975C3.1037 9.73421 5.83773 11.4683 8.81641 11.3748C11.795 11.4678 14.5287 9.73388 15.714 6.99975C14.5305 4.26413 11.7956 2.52945 8.81641 2.62475ZM8.81641 9.62475C7.55407 9.63311 6.4621 8.74744 6.20975 7.51055C5.9574 6.27367 6.61514 5.03102 7.77987 4.54421C8.94459 4.05739 10.291 4.46237 10.9939 5.51095C11.6968 6.55952 11.5599 7.95882 10.667 8.85125C10.1782 9.34585 9.51182 9.62437 8.81641 9.62475Z" fill="currentcolor"/>
                                </svg>
                                <span>Show</span>
                            </a>
                        </li>
                        <li class="data-table-list-item list-group-item border-0 p-0 bg-transparent">
                            <a href="' . route('admin.user-management.roles.edit', $row->id) . '" class="edit d-flex align-items-center gap-2 text-info">
                                <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M14.3671 10.0991V13.8903C14.3671 14.3091 13.9949 14.6486 13.5358 14.6486H1.8977C1.43859 14.6486 1.06641 14.3091 1.06641 13.8903V3.27485C1.06641 2.85608 1.43859 2.5166 1.8977 2.5166H6.05416" stroke="currentcolor" stroke-width="1.51651" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6.05469 10.099H8.54857L16.0302 3.27474L13.5363 1L6.05469 7.82423V10.099Z" stroke="currentcolor" stroke-width="1.51651" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M11.041 3.2749L13.5349 5.54965" stroke="currentcolor" stroke-width="1.51651" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>                        
                                <span>Edit</span>
                            </a>                    
                        </li>
                        <li class="data-table-list-item list-group-item border-0 p-0 bg-transparent">
                            <a href="javascript:void(0)" class="delete text-danger d-flex align-items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_184_370)">
                                <path d="M6.47746 0.576407C6.24241 0.636247 6.02243 0.764476 5.83862 0.949696C5.54933 1.2432 5.48605 1.45406 5.48605 2.1408V2.66226H3.66295H1.83984L1.84587 3.52282L1.85491 4.38623H7.74609H13.6373L13.6463 3.52282L13.6523 2.66226H11.8323H10.0092L10.0001 2.08381C9.99107 1.56235 9.98203 1.49396 9.91875 1.34293C9.78917 1.03233 9.46674 0.733132 9.13225 0.604903C8.97254 0.542213 8.92734 0.539363 7.82143 0.533664C6.81194 0.527965 6.65223 0.533664 6.47746 0.576407ZM8.86105 1.44266C9.07801 1.55095 9.10212 1.62503 9.10212 2.18354V2.66226H7.74609H6.39007V2.18354C6.39007 1.62788 6.41417 1.5538 6.63114 1.44551C6.7577 1.37998 6.77879 1.37998 7.74308 1.37998C8.70435 1.37998 8.73147 1.38282 8.86105 1.44266Z" fill="currentcolor"/>
                                <path d="M2.83398 5.63611C2.83398 5.64181 3.0178 7.62508 3.24079 10.0443C3.50296 12.9024 3.66267 14.4981 3.6928 14.5922C3.82238 14.9997 4.19604 15.3302 4.66311 15.4442C4.92227 15.5069 10.5694 15.5069 10.8285 15.4442C11.2956 15.3302 11.6693 14.9997 11.7988 14.5922C11.829 14.4981 11.9887 12.9024 12.2508 10.0443C12.4738 7.62508 12.6576 5.64181 12.6576 5.63611C12.6576 5.63041 10.4488 5.62756 7.74581 5.62756C5.04581 5.62756 2.83398 5.63041 2.83398 5.63611ZM6.38376 10.1213L6.37472 12.4807L5.93175 12.4892L5.48577 12.4978V10.1298V7.76471H5.93778H6.38979L6.38376 10.1213ZM8.19782 10.1298V12.4949H7.74581H7.29381V10.1298V7.76471H7.74581H8.19782V10.1298ZM10.0059 10.1298V12.4978L9.56289 12.4892L9.11691 12.4807L9.10787 10.1213L9.10184 7.76471H9.55385H10.0059V10.1298Z" fill="currentcolor"/>
                                </g>
                                <defs>
                                <clipPath id="clip0_184_370">
                                <rect width="15.4286" height="15.4286" fill="white" transform="translate(0.0664062)"/>
                                </clipPath>
                                </defs>
                                </svg>
                                <span>Delete</span>
                            </a>                    
                        </li>
                    </ul>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view(self::ROLE_INDEX_PATH);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permissions = $this->permissionRepository->getAllPermissions();

        return view('admin.user-management.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoleCreateRequest $request)
    {
        $roleDetails = $request->only([
            'name',
            'slug',
        ]);
        $this->roleRepository->createRole($roleDetails, $request->permissions);

        return redirect()->route(self::ROLE_INDEX_PATH);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = $this->roleRepository->getRoleById($id);

        return view('admin.user-management.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = $this->roleRepository->getRoleById($id);
        $permissions = $this->permissionRepository->getAllPermissions();

        return view('admin.user-management.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RoleUpdateRequest $request, $id)
    {
        $roleDetails = $request->only([
            'name',
            'slug',
        ]);
        $this->roleRepository->updateRole($id, $roleDetails, $request->permissions);

        return redirect()->route(self::ROLE_INDEX_PATH);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->roleRepository->deleteRole($id);

        return redirect()->route(self::ROLE_INDEX_PATH);
    }
}
