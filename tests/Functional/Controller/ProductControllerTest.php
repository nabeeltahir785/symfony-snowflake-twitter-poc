<?php

namespace App\Tests\Functional\Controller;

use App\Repository\ProductRepository;
use App\Service\SnowflakeIdGenerator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends WebTestCase
{
    /**
     * Test listing all products
     */
    public function testListProducts(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    /**
     * Test creating a product
     */
    public function testCreateProduct(): void
    {
        $client = static::createClient();

        $productData = [
            'name' => 'Test Product',
            'description' => 'This is a test product',
            'price' => '29.99',
            'stock' => 50,
        ];

        $client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($productData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertEquals($productData['name'], $responseData['name']);

        // Verify the ID is a valid Snowflake ID
        $this->assertIsString($responseData['id']);
        $this->assertNotEmpty($responseData['id']);

        // Get the SnowflakeIdGenerator service to validate the ID
        $snowflakeGenerator = static::getContainer()->get(SnowflakeIdGenerator::class);
        $timestamp = $snowflakeGenerator->extractTimestamp($responseData['id']);

        // Check timestamp is recent (within the last minute)
        $now = (int)(microtime(true) * 1000);
        $this->assertLessThanOrEqual($now, $timestamp);
        $this->assertGreaterThan($now - 60000, $timestamp); // Within last minute
    }

    /**
     * Test getting a single product
     *
     * @depends testCreateProduct
     */
    public function testGetProduct(string $productId): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products/' . $productId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($productId, $responseData['id']);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertEquals('Test Product', $responseData['name']);
    }

    /**
     * Test updating a product
     *
     * @depends testGetProduct
     */
    public function testUpdateProduct(string $productId): void
    {
        $client = static::createClient();

        $updateData = [
            'name' => 'Updated Product',
            'description' => 'This product has been updated',
            'price' => '39.99',
            'stock' => 75,
        ];

        $client->request(
            'PUT',
            '/api/products/' . $productId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updateData)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($productId, $responseData['id']);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertEquals($updateData['name'], $responseData['name']);
        $this->assertArrayHasKey('updatedAt', $responseData);
        $this->assertNotNull($responseData['updatedAt']);

    }

    /**
     * Test low stock endpoint
     */
    public function testLowStockProducts(): void
    {
        $client = static::createClient();

        // Create a product with low stock
        $productData = [
            'name' => 'Low Stock Product',
            'description' => 'This product has low stock',
            'price' => '49.99',
            'stock' => 2,
        ];

        $client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($productData)
        );

        $this->assertResponseIsSuccessful();

        // Test the low-stock endpoint
        $client->request('GET', '/api/products/low-stock?threshold=5');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);

        // Verify our low stock product is in the results
        $found = false;
        foreach ($responseData as $product) {
            if ($product['name'] === 'Low Stock Product' && $product['stock'] === 2) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Low stock product not found in response');
    }

    /**
     * Test snowflake info endpoint
     */
    public function testSnowflakeInfo(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/products/snowflake-info');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('timestamp', $responseData);
        $this->assertArrayHasKey('date', $responseData);
        $this->assertArrayHasKey('node_id', $responseData);
        $this->assertArrayHasKey('sequence', $responseData);
        $this->assertArrayHasKey('info', $responseData);

        // Verify the structure of the info object
        $this->assertArrayHasKey('timestamp_bits', $responseData['info']);
        $this->assertArrayHasKey('node_id_bits', $responseData['info']);
        $this->assertArrayHasKey('sequence_bits', $responseData['info']);

        // Verify the values are correct
        $this->assertEquals(41, $responseData['info']['timestamp_bits']);
        $this->assertEquals(10, $responseData['info']['node_id_bits']);
        $this->assertEquals(12, $responseData['info']['sequence_bits']);
    }

    /**
     * Test deleting a product
     *
     * @depends testUpdateProduct
     */
    public function testDeleteProduct(string $productId): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/products/' . $productId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Try to get the deleted product
        $client->request('GET', '/api/products/' . $productId);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test validation errors
     */
    public function testValidationErrors(): void
    {
        $client = static::createClient();

        $invalidData = [
            'name' => '', // Empty name should fail validation
            'price' => '-10.00', // Negative price should fail validation
            'stock' => -5, // Negative stock should fail validation
        ];

        $client->request(
            'POST',
            '/api/products',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }
}