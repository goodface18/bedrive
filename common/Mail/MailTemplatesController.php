<?php namespace Common\Mail;

use Exception;
use Common\Mail\MailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Common\Mail\MailTemplates;
use Illuminate\Filesystem\Filesystem;
use Common\Mail\MailTemplatePreview;
use Common\Core\Controller;

class MailTemplatesController extends Controller
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var MailTemplates
     */
    private $templates;

    /**
     * @var MailTemplatePreview
     */
    private $preview;

    /**
     * MailTemplatesController constructor.
     *
     * @param Filesystem $fs
     * @param Request $request
     * @param MailTemplates $templates
     * @param MailTemplatePreview $preview
     */
    public function __construct(Filesystem $fs, Request $request, MailTemplates $templates, MailTemplatePreview $preview)
    {
        $this->fs = $fs;
        $this->request = $request;
        $this->preview = $preview;
        $this->templates = $templates;
    }

    /**
     * Get all mail templates.
     *
     * @return \Illuminate\Support\Collection
     */
    public function index()
    {
        $this->authorize('index', MailTemplate::class);

        return $this->templates->getAll(['forceCustom' => true]);
    }

    /**
     * Update specified mail template with data.
     *
     * @param integer $id
     *
     * @return array|JsonResponse
     */
    public function update($id)
    {
        $this->authorize('update', MailTemplate::class);

        $this->validate($this->request, [
            'subject' => 'required|string|min:1|max:255',
            'contents.html'  => 'required|string|min:1',
            'contents.plain' => 'string|min:1|nullable',
        ]);

        //make sure specified blade template renders without errors
        if ( ! is_array($response = $this->render())) return $response;

        return $this->templates->update($id, $this->request->all());
    }

    /**
     * Restore specified mail template to default values.
     *
     * @param int $id
     * @return array
     */
    public function restoreDefault($id)
    {
        $this->authorize('update', MailTemplate::class);

        return $this->templates->restoreDefault($id);
    }

    /**
     * Render blade email template into string.
     *
     * @return array|JsonResponse
     */
    public function render()
    {
        $this->authorize('show', MailTemplate::class);

        $data = $this->request->all();

        //if we have both "plain" and "html" contents, use html
        if (is_array($data['contents'])) {
            $data['contents'] = $data['contents']['html'];
        }

        try {
            return $this->preview->render($data);
        } catch (Exception $e) {
            return $this->error();
        }
    }

}
