<?php

namespace Dergipark\WorkflowBundle\Controller;

use Dergipark\WorkflowBundle\Entity\ArticleWorkflowStep;
use Dergipark\WorkflowBundle\Entity\DialogPost;
use Dergipark\WorkflowBundle\Entity\StepDialog;
use Dergipark\WorkflowBundle\Params\DialogPostTypes;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Pagerfanta\Exception\LogicException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $this->throw404IfNotFound($journal);
        $em = $this->getDoctrine()->getManager();
        $workflowService = $this->get('dp.workflow_service');
        $workflow = $workflowService->getArticleWorkflow($workflowId);
        $step = $em->getRepository(ArticleWorkflowStep::class)->findOneBy([
            'articleWorkflow' => $workflow,
            'order' => $stepOrder,
        ]);
        $dialogs = $em->getRepository(StepDialog::class)->findBy([
            'step' => $step,
        ]);

        return $this->render('DergiparkWorkflowBundle:StepDialog:_step_dialogs.html.twig', [
            'dialogs' => $dialogs,
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
}
