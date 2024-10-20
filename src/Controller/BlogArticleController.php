<?php

namespace App\Controller;

use App\Enum\Status;
use App\Entity\BlogArticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogArticleController extends AbstractController
{
    private $em;
    private $banned = ["dog", "pig", "cow", "monkey"];

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }
    
    #[Route('/blog-articles', name: 'createArticle', methods: ['POST'])]
    public function createArticle(Request $request){
        $format = 'Y-m-d H:i:s';
        
        $article = new BlogArticle();

        $authorId = $request->get('authorId');
        if ($authorId === null || !is_numeric($authorId)) {
            return new JsonResponse(['code' => 400, 'message' => 'authorId must be a valid integer.'], Response::HTTP_BAD_REQUEST);
        }
        $article->setAuthorId((int) $authorId);

        $title = $request->get('title');
        if (is_null($title) || empty($title)) {
            return new JsonResponse('Title cannot be blank', Response::HTTP_BAD_REQUEST);
        }
        $article->setTitle($title);

        $publicationDateString = $request->get('publicationDate');
        $publicationDate = \DateTime::createFromFormat($format, $publicationDateString);
        if (!$publicationDate) {
            return new JsonResponse('Invalid publication date', Response::HTTP_BAD_REQUEST);
        }
        $article->setPublicationDate($publicationDate);
        
        $creationDateString = $request->get('creationDate');
        $creationDate = \DateTime::createFromFormat($format, $creationDateString);
        if (!$creationDate) {
            return new JsonResponse('Invalid creation date', Response::HTTP_BAD_REQUEST);
        }
        $article->setCreationDate($creationDate);

        if ($this->validateContent($request->get('content'))) {
            $article->setContent($request->get('content'));
        } else {
            return new JsonResponse('Invalid content! Your content contains banned words!', Response::HTTP_BAD_REQUEST);
        }

        $keywordsJson = $request->get('keywords');
        $keywordsArray = json_decode($keywordsJson, true);
        $moreKeywords = $this->topThreeWords($request->get('content'));
        $keywordsArray = array_merge($keywordsArray, $moreKeywords);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new Response('Invalid JSON for keywords', Response::HTTP_BAD_REQUEST);
        }
        $article->setKeywords($keywordsArray);

        try {
            $status = Status::from($request->get('status'));
            $article->setStatus($status);
        } catch (\ValueError $e) {
            return new JsonResponse(['code' => 400, 'message' => 'Invalid status value.'], Response::HTTP_BAD_REQUEST);
        }

        $article->setSlug($request->get('slug'));
        
        if ($request->files->has('coverPictureRef')) {
            $file = $request->files->get('coverPictureRef');
            $fileName = md5(uniqid()) . '.' . $file->guessExtension();
            $file->move($this->getParameter('upload_directory'), $fileName);
            $article->setCoverPictureRef($fileName);
        }

        $article->setDeleted(false);

        $this->em->persist($article);
        $this->em->flush();
        
        return new JsonResponse(['code' => 200, 'message' => "Article with title '".$request->get('title')."' was created successfully!"], Response::HTTP_OK);
    }

    #[Route('/blog-articles', name: 'getArticles', methods: ['GET'])]
    public function getArticles(Request $request){
        $data = [];
        $articles = $this->em->getRepository(BlogArticle::class)->findBy([
            "isDeleted" => false
        ]);
        
        if(!$articles){
            $return = json_encode(['code' => 200, 'message' => "NO DATA FOUND :("]);
            return new JsonResponse($return, Response::HTTP_OK, [], true);
        }

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'authorId' => $article->getAuthorId(),
                'title' => $article->getTitle(),
                'publicationDate' => $article->getPublicationDate()?->format('Y-m-d H:i:s'),
                'creationDate' => $article->getCreationDate()->format('Y-m-d H:i:s'),
                'content' => $article->getContent(),
                'keywords' => $article->getKeywords(),
                'status' => $article->getStatus()?->value,
                'slug' => $article->getSlug(),
                'coverPictureRef' => $article->getCoverPictureRef(),
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/blog-articles/{id}', name: 'getArticleById', methods: ['GET'])]
    public function getArticleById(int $id): JsonResponse
    {
        $article = $this->em->getRepository(BlogArticle::class)->findOneBy([
            "id" => $id,
            "isDeleted" => false
        ]);

        if (!$article) {
            $return = json_encode(['code' => 200, 'message' => 'Article not found']);
            return new JsonResponse($return, Response::HTTP_OK, [], true);
        }
        
        $data = [
            'id' => $article->getId(),
            'authorId' => $article->getAuthorId(),
            'title' => $article->getTitle(),
            'publicationDate' => $article->getPublicationDate()?->format('Y-m-d H:i:s'),
            'creationDate' => $article->getCreationDate()->format('Y-m-d H:i:s'),
            'content' => $article->getContent(),
            'keywords' => $article->getKeywords(),
            'status' => $article->getStatus()?->value,
            'slug' => $article->getSlug(),
            'coverPictureRef' => $article->getCoverPictureRef(),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/blog-articles/{id}', name: 'updateArticle', methods: ['PATCH'])]
    public function updateArticle(Request $request, int $id): JsonResponse
    {
        $format = 'Y-m-d H:i:s';
        $requestData = json_decode($request->getContent(), true);
        
        if(!$requestData){
            $return = json_encode(['code' => 200, 'message' => 'Article not found']);
            return new JsonResponse($return, Response::HTTP_OK, [], true);
        }

        $article = $this->em->getRepository(BlogArticle::class)->findOneBy([
            "id" => $id,
            "isDeleted" => false
        ]);
        
        if (!$article) {
            throw new NotFoundHttpException('Article not found');
        }
        
        if (ISSET($requestData['authorId']) && $requestData['authorId'] !== null) {
            $authorId = $requestData['authorId'];
            if ($authorId === null || !is_numeric($authorId)) {
                return new JsonResponse(['code' => 400, 'message' => 'authorId must be a valid integer.'], Response::HTTP_BAD_REQUEST);
            }
            $article->setAuthorId((int) $authorId);
        }
        
        if (ISSET($requestData['title']) && $requestData['title'] !== null) {
            $article->setTitle($requestData['title']);
        }

        if (ISSET($requestData['publicationDate']) && $requestData['publicationDate'] !== null) {
            $publicationDateString = $requestData['publicationDate'];
            $publicationDate = \DateTime::createFromFormat($format, $publicationDateString);
            if (!$publicationDate) {
                return new JsonResponse('Invalid publication date', Response::HTTP_BAD_REQUEST);
            }
            $article->setPublicationDate($publicationDate);
        }

        if (ISSET($requestData['creationDate']) && $requestData['creationDate'] !== null) {
            $creationDateString = $requestData['creationDate'];
            $creationDate = \DateTime::createFromFormat($format, $creationDateString);
            if (!$creationDate) {
                return new JsonResponse('Invalid creation date', Response::HTTP_BAD_REQUEST);
            }
            $article->setCreationDate($creationDate);
        }

        if (ISSET($requestData['content']) && $requestData['content'] !== null) {
            if ($this->validateContent($requestData['content'])) {
                $article->setContent($requestData['content']);
            } else {
                return new JsonResponse('Invalid content! Your content contains banned words!', Response::HTTP_BAD_REQUEST);
            }
            $article->setContent($requestData['content']);
            $keywordsArray = $article->getKeywords();
            $moreKeywords = $this->topThreeWords($requestData['content']);
            $keywordsArray = array_merge($keywordsArray, $moreKeywords);
            $article->setKeywords($keywordsArray);
        }

        if (ISSET($requestData['keywords']) && $requestData['keywords'] !== null) {
            $keywordsArray = $requestData['keywords'];
            if (ISSET($requestData['content']) && $requestData['content'] !== null) {
                $moreKeywords = $this->topThreeWords($requestData['content']);
                $keywordsArray = array_merge($keywordsArray, $moreKeywords);
            }
            $article->setKeywords($keywordsArray);
        }

        if (ISSET($requestData['status']) && $requestData['status'] !== null) {
            try {
                $status = Status::from($requestData['status']);
                $article->setStatus($status);
            } catch (\ValueError $e) {
                return new JsonResponse(['code' => 400, 'message' => 'Invalid status value.'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (ISSET($requestData['slug']) && $requestData['slug'] !== null) {
            $article->setSlug($requestData['slug']);
        }

        $this->em->persist($article);
        $this->em->flush();

        return new JsonResponse(['message' => 'Article updated successfully!'], Response::HTTP_OK);
    }

    #[Route('/blog-articles/{id}', name: 'deleteArticle', methods: ['DELETE'])]
    public function deleteArticle($id): Response
    {
        $article = $this->em->getRepository(BlogArticle::class)->find($id);

        if (!$article) {
            return $this->json(['message' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }
        
        $article->setDeleted(true);
        $this->em->persist($article);
        $this->em->flush();

        return $this->json(['message' => 'Article soft-deleted'], Response::HTTP_OK);
    }

    function topThreeWords($text) {

        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $words = preg_split('/\s+/', $text);
        
        $bannedSet = array_flip($this->banned);
        
        $wordCount = [];
        foreach ($words as $word) {
            if (!isset($bannedSet[$word]) && !empty($word)) {
                if (!isset($wordCount[$word])) {
                    $wordCount[$word] = 0;
                }
                $wordCount[$word]++;
            }
        }
        
        arsort($wordCount);
        return array_slice(array_keys($wordCount), 0, 3);
    }

    function validateContent($text) {
        $bannedSet = array_flip($this->banned);
        $words = preg_split('/\s+/', strtolower($text));
        
        foreach ($words as $word) {
            if (isset($bannedSet[$word])) {
                return false;
            }
        }
        return true;
    }
}