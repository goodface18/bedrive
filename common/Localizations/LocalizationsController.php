<?php namespace Common\Localizations;

use Illuminate\Http\Request;
use Common\Core\Controller;
use Illuminate\Support\Collection;

class LocalizationsController extends Controller
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var LocalizationsRepository
     */
    private $repository;

    /**
     * LocalizationsController constructor.
     *
     * @param Request $request
     * @param LocalizationsRepository $repository
     */
    public function __construct(Request $request, LocalizationsRepository $repository)
    {
        $this->request = $request;
        $this->repository = $repository;
    }

    /**
     * Return all user created localizations.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('index', Localization::class);

        return $this->success(['localizations' =>  $this->repository->all()]);
    }

    /**
     * Get localization by specified name.
     *
     * @param string $name
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function show($name)
    {
        $this->authorize('show', Localization::class);

        return $this->success(['localization' => $this->repository->getByName($name)]);
    }

    /**
     * Update specified localization.
     *
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function update($id)
    {
        $this->authorize('update', Localization::class);

        $this->validate($this->request, [
            'name'  => 'string|min:1',
            'lines' => 'array|min:1'
        ]);

        $localization = $this->repository->update($id, $this->request->all());
        return $this->success(['localization' => $localization]);
    }

    /**
     * Create a new localization
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function store()
    {
        $this->authorize('store', Localization::class);

        $this->validate($this->request, [
            'name' => 'required|unique:localizations'
        ]);

        $localization = $this->repository->create($this->request->get('name'));
        return $this->success(['localization' => $localization]);
    }

    /**
     * Delete specified language.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $this->authorize('destroy', Localization::class);

        $this->repository->delete($id);

        return $this->success();
    }
}
