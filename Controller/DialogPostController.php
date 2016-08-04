<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\DialogPost;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Params\DialogPostTypes;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Pagerfanta\Exception\LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DialogPostController extends Controller
{
    /**
     * @param Request $request
     * @param $workflowId
     * @param $stepOrder
     * @param $dialogId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getPostsAction(Request $request, $workflowId, $stepOrder, $dialogId)
    {
        $em = $this->getDoctrine()->getManager();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);

        $posts = $em->getRepository(DialogPost::class)->findBy([
            'dialog' => $dialog,
        ], [
            'id' => 'ASC',
        ]);

        return $this->render('DergiparkWorkflowBundle:DialogPost:_dialog_posts.html.twig', [
            'posts' => $posts,
            'filePostType' => DialogPostTypes::TYPE_FILE,
            'textPostType' => DialogPostTypes::TYPE_TEXT,
        ]);
    }

    /**
     * @param Request $request
     * @param $dialogId
     * @return LogicException|JsonResponse
     */
    public function postCommentAction(Request $request, $dialogId)
    {
        $comment = $request->get('comment');
        if(!$comment){
            return new LogicException('comment param must be!');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);

        $post = new DialogPost();
        $post
            ->setType(DialogPostTypes::TYPE_TEXT)
            ->setText($comment)
            ->setDialog($dialog)
            ->setSendedAt(new \DateTime())
            ->setSendedBy($user)
            ;
        $em->persist($post);
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @param Request $request
     * @param $dialogId
     * @return LogicException|JsonResponse
     */
    public function browseFilesAction(Request $request, $workflowId, $dialogId)
    {
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);

        return $this->render('DergiparkWorkflowBundle:DialogPost:_browse_files.html.twig', [
            'files' => $workflowService->getUserRelatedFiles($workflow),
            'dialogId' => $dialogId,
        ]);
    }

    /**
     * @param Request $request
     * @param $dialogId
     * @return LogicException|JsonResponse
     */
    public function postFileAction(Request $request, $dialogId)
    {
        $files = $request->request->get('files');
        if(!$files){
            return new LogicException('files param must be!');
        }
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $dialog = $em->getRepository(StepDialog::class)->find($dialogId);

        foreach($files as $file){
            $filePost = new DialogPost();
            $filePost
                ->setDialog($dialog)
                ->setSendedBy($user)
                ->setSendedAt(new \DateTime())
                ->setFileName($file['fileName'])
                ->setFileOriginalName($file['fileOriginalName'])
                ->setType(DialogPostTypes::TYPE_FILE)
                ;
            $em->persist($filePost);
        }
        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
