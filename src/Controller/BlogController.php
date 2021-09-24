<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Controller\SecurityController;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Config\Framework\RequestConfig;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use function Sodium\add;


class BlogController extends AbstractController
{
    /**
     * @Route("/blog", name="blog")
     */
    public function index(ArticleRepository $repo): Response
    {
        $articles = $repo->findAll();

        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
            'articles' => $articles
        ]);
    }

    /**
     * @Route("/"), name="home")
     */
    public function home(){

        return $this->render('blog/home.html.twig', [
            'title' => "Welcome"
        ]);
    }

    /**
     * @Route("/blog/new", name="blog_create")
     * @Route("/blog/{id}/edit", name="blog_edit")
     */
    public function form(Article $article = null, Request $request, EntityManagerInterface $manager){

        $user = $this->getUser();

        if($user->email === 'admin@admin.admin'){

            if (!$article){
                $article = new Article();
            }

            $form = $this->createFormBuilder($article)
                ->add('titre')
                ->add('category', EntityType::class, [
                    'class' => Category::class,
                    'choice_label' => 'titre'
                ])
                ->add('contenu')
                ->add('image')
                ->getForm();

            $form ->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()){
                if (!$article->getId()){
                    $article->setCreatedAt(new \DateTime());
                }

                $manager->persist($article);
                $manager->flush();

                return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
            }

            return $this->render('blog/create.html.twig',[
                'formArticle' => $form->createView(),
                'editMode' => $article->getId() !== null,
            ]);
        }
        else{
            return $this->redirectToRoute('blog');
        }

    }

    /**
     * @Route ("/blog/{id}", name="blog_show")
     */
    public function show(Article $article, Request $request, EntityManagerInterface $manager){

        $comment= new Comment();

        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted()&& $form->isValid()){

            $comment->setCreatedAt(new \DateTime())
                    ->setArticle($article);

            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('blog_show', ['id' => $article->getId()]);
        }

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView()
        ]);

    }
}
