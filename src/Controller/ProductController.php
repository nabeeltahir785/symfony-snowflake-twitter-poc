<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Message\ProductUpdated;
use App\Repository\ProductRepository;
use App\Service\SnowflakeIdGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/products")
 */
class ProductController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var SnowflakeIdGenerator
     */
    private SnowflakeIdGenerator $snowflakeGenerator;

    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $messageBus;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @param SnowflakeIdGenerator $snowflakeGenerator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        SnowflakeIdGenerator $snowflakeGenerator,
        MessageBusInterface $messageBus
    ) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->snowflakeGenerator = $snowflakeGenerator;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("", name="product_list", methods={"GET"})
     */
    public function list(ProductRepository $repository): JsonResponse
    {
        $products = $repository->findAll();

        return $this->json($products, Response::HTTP_OK, [], [
            'groups' => ['product:read']
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods={"GET"})
     */
    public function show(string $id, ProductRepository $repository): JsonResponse
    {
        $product = $repository->find($id);

        if (!$product) {
            return $this->json(['message' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => ['product:read']
        ]);
    }

    /**
     * @Route("", name="product_create", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->submit($data);

        if (!$form->isValid()) {
            return $this->json(['errors' => $this->getFormErrors($form)], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_CREATED, [], [
            'groups' => ['product:read']
        ]);
    }

    /**
     * @Route("/{id}", name="product_update", methods={"PUT", "PATCH"})
     */
    public function update(string $id, Request $request, ProductRepository $repository): JsonResponse
    {
        $product = $repository->find($id);

        if (!$product) {
            return $this->json(['message' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->submit($data, $request->getMethod() !== 'PATCH');

        if (!$form->isValid()) {
            return $this->json(['errors' => $this->getFormErrors($form)], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        // Dispatch a message to the message bus for async processing
        $this->messageBus->dispatch(new ProductUpdated(
            $product->getId(),
            new \DateTime()
        ));

        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => ['product:read']
        ]);
    }

    /**
     * @Route("/{id}", name="product_delete", methods={"DELETE"})
     */
    public function delete(string $id, ProductRepository $repository): JsonResponse
    {
        $product = $repository->find($id);

        if (!$product) {
            return $this->json(['message' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/low-stock", name="product_low_stock", methods={"GET"})
     */
    public function lowStock(ProductRepository $repository, Request $request): JsonResponse
    {
        $threshold = $request->query->getInt('threshold', 5);
        $products = $repository->findLowStock($threshold);

        return $this->json($products, Response::HTTP_OK, [], [
            'groups' => ['product:read']
        ]);
    }

    /**
     * @Route("/snowflake-info", name="snowflake_info", methods={"GET"})
     */
    public function snowflakeInfo(): JsonResponse
    {
        // Generate a sample Snowflake ID
        $id = $this->snowflakeGenerator->nextId();

        // Extract components from the ID
        $timestamp = $this->snowflakeGenerator->extractTimestamp($id);
        $nodeId = $this->snowflakeGenerator->extractNodeId($id);
        $sequence = $this->snowflakeGenerator->extractSequence($id);

        // Convert timestamp to date
        $date = new \DateTime();
        $date->setTimestamp(intval($timestamp / 1000));

        return $this->json([
            'id' => $id,
            'timestamp' => $timestamp,
            'date' => $date->format('Y-m-d H:i:s.v'),
            'node_id' => $nodeId,
            'sequence' => $sequence,
            'info' => [
                'timestamp_bits' => 41,
                'node_id_bits' => 10,
                'sequence_bits' => 12,
            ],
            'generated_at' => (new \DateTime())->format('Y-m-d H:i:s.v'),
        ]);
    }

    /**
     * Extract form errors
     *
     * @param \Symfony\Component\Form\FormInterface $form
     * @return array
     */
    private function getFormErrors($form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $propertyPath = $error->getCause()->getPropertyPath();
            $errors[$propertyPath] = $error->getMessage();
        }

        return $errors;
    }
}