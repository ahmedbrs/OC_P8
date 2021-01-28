<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/tasks/{done?}", name="task_list", requirements={"done"="\d+"})
     */
    public function listAction(TaskRepository $taskRepository, $done)
    {
        $tasks = ($done) ? $taskRepository->findBy(['isDone' => true]) : $taskRepository->findBy(['isDone' => false]);

        return $this->render('task/list.html.twig', [
            'tasks' => $tasks
        ]);
    }

    /**
     * @Route("/tasks/create", name="task_create")
     */
    public function createAction(Request $request)
    {
        $task = new Task();
        $user = $this->getUser() ?? false;
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user) {
                $task->setUser($user);
            }
            $this->em->persist($task);
            $this->em->flush();

            $this->addFlash('success', 'La tâche a été bien été ajoutée.');

            return $this->redirectToRoute('task_list', [
                'done' => false,
            ]);
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/tasks/{id}/edit", name="task_edit", requirements={"id"="\d+"})
     */
    public function editAction(Task $task, Request $request)
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($task);
            $this->em->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list', [
                'done' => false,
            ]);
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    /**
     * @Route("/tasks/{id}/toggle", name="task_toggle", requirements={"id"="\d+"})
     */
    public function toggleTaskAction(Task $task)
    {
        $task->toggle(!$task->getIsDone());
        $this->em->persist($task);
        $this->em->flush();

        $state = ($task->getIsDone()) ? 'faite' : 'non terminée';

        $this->addFlash('success', sprintf(
            'La tâche \' %s \' a bien été marquée comme %s.', 
            $task->getTitle(),
            $state
        ));

        return $this->redirectToRoute('task_list', [
            'done' => false,
        ]);
    }

    /**
     * @Route("/tasks/{id}/delete", name="task_delete", requirements={"id"="\d+"})
     */
    public function deleteTaskAction(Task $task)
    {
        if ($task->getUser() == $this->getUser()) {
            $this->em->remove($task);
            $this->em->flush();

            $this->addFlash('success', 'La tâche a bien été supprimée.');
        } else {
            $this->addFlash('error', "Cette tâche ne peut être supprimée que par son auteur.");
        }

        return $this->redirectToRoute('task_list');
    }
}
