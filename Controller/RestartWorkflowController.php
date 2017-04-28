<?php

namespace Vipa\WorkflowBundle\Controller;

use Vipa\WorkflowBundle\Entity\ArticleWorkflow;
use Vipa\WorkflowBundle\Params\ArticleWorkflowStatus;
use Vipa\CoreBundle\Controller\VipaController as Controller;
use Vipa\CoreBundle\Params\ArticleStatuses;
use Vipa\JournalBundle\Entity\Article;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class RestartWorkflowController extends Controller
{
    /**
     * @param Request $request
     * @param Article $article
     * @return Response
     */
    public function restartAction(Request $request, Article $article)
    {
        $journal = $this->get('vipa.journal_service')->getSelectedJournal();
        if (!$this->isGranted('VIEW', $journal, 'articles')) {
            throw new AccessDeniedException("You not authorized for this page!");
        }
        $em = $this->getDoctrine()->getManager();
        $translator = $this->get('translator');
        $workflowService = $this->get('dp.workflow_service');

        $availableStatus = [
            ArticleStatuses::STATUS_INREVIEW,
            ArticleStatuses::STATUS_REJECTED,
            ArticleStatuses::STATUS_WITHDRAWN,
        ];
        if (!in_array($article->getStatus(), $availableStatus)) {
            $this->errorFlashBag($translator->trans('can.not.start.workflow.because.status', [
                '%status%' => $translator->trans($article->getStatusText())
            ]));

            return $this->redirectToRoute('vipa_journal_article_index', array('journalId' => $journal->getId()));
        }

        $currentFlow = $em->getRepository(ArticleWorkflow::class)->findOneBy(
            array(
                'article' => $article,
                'status' => ArticleWorkflowStatus::ACTIVE,
            )
        );
        if(!$currentFlow){
            $workflow = $workflowService->prepareArticleWorkflow($article);

            return $this->redirectToRoute(
                'vipa_workflow_article_workflow',
                [
                    'journalId' => $journal->getId(),
                    'workflowId' => $workflow->getId(),
                ]
            );
        }

        $form = $this->createFormBuilder()
            ->add('confirmText', TextType::class, [
                'label' => 'fill.confirm.area',
            ])
            ->add('sure', 'submit', [
                'label' => 'wf.restart.sure',
                'disabled' => true,
                'attr' => [
                    'class' => 'col-xs-2',
                ]
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            if($currentFlow instanceof ArticleWorkflow){
                $workflowService->closeOldWorklfows($article, true);
            }
            $workflow = $workflowService->prepareArticleWorkflow($article);

            return $this->redirectToRoute(
                'vipa_workflow_article_workflow',
                [
                    'journalId' => $journal->getId(),
                    'workflowId' => $workflow->getId(),
                ]
            );
        }

        return $this->render(
            'VipaWorkflowBundle:RestartWorkflow:_confirm_restart.html.twig',
            [
                'article' => $article,
                'currentWorkflow' => $currentFlow,
                'form' => $form->createView()
            ]
        );
    }
}
