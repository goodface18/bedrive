<?php namespace Common\Pages;

use Common\Pages\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Common\Core\Controller;

class PagesController extends Controller
{
    /**
     * @var Page
     */
    private $page;

    /**
     * @var Request
     */
    private $request;

    /**
     * PagesController constructor.
     *
     * @param Page $page
     * @param Request $request
     */
    public function __construct(Page $page, Request $request)
    {
        $this->page = $page;
        $this->request = $request;
    }

    /**
     * Return all existing pages.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        $this->authorize(Page::class);

        $orderBy    = $this->request->get('order_by', 'created_at');
        $orderDir   = $this->request->get('order_dir', 'desc');
        $perPage    = $this->request->get('per_page', '13');
        $searchTerm = $this->request->get('query');

        $query = $this->page->newQuery()->where('type', Page::DEFAULT_PAGE_TYPE);

        if ($searchTerm) {
            $query->where('slug', 'LIKE', "%$searchTerm%");
            $query->orWhere('body', 'LIKE', "$searchTerm%");
        }

        $paginator = $query->orderBy($orderBy, $orderDir)->paginate($perPage);

        $paginator->map(function($page) {
            $page->body = str_limit(strip_tags($page->body), 200);
            return $page;
        });

        return $paginator;
    }

    /**
     * Find page by specified id.
     *
     * @param string $id
     * @return Page
     */
    public function show($id)
    {
        $this->authorize('show', Page::class);

        return $this->page->where('id', $id)->firstOrFail();
    }

    /**
     * Create a new page.
     *
     * @return Response
     */
    public function store()
    {
        $this->authorize('store', Page::class);

        $this->validate($this->request, [
            'slug' => 'required|string|max:100|unique:pages',
            'body' => 'required|min:1'
        ]);

        return response($this->page->create($this->request->all()), 201);
    }

    /**
     * Update existing page.
     *
     * @param integer $id
     * @return Page
     */
    public function update($id)
    {
        $this->authorize('update', Page::class);

        $this->validate($this->request, [
            'slug' => 'string|max:100|unique:pages,slug,'.$id,
            'body' => 'min:1'
        ]);

        $page = $this->page->find($id)->fill($this->request->except('id'));
        $page->save();

        return $page;
    }

    /**
     * Delete pages matching specified ids.
     *
     * @return Response
     */
    public function destroy()
    {
        $this->authorize('destroy', Page::class);

        $this->validate($this->request, [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer'
        ]);

        $this->page->whereIn('id', $this->request->get('ids'))->delete();

        return response(null, 204);
    }
}
