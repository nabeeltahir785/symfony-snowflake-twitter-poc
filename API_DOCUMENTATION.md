
# Snowflake Service API Documentation

## Overview

This API provides access to the products managed by the Snowflake ID Generator service. All resources use Snowflake IDs as unique identifiers.

## Authentication

This demo API doesn't include authentication. In a production environment, you would use JWT, OAuth2, or another secure authentication method.

## Base URL

```
http://localhost/api
```

## Endpoints

### Product Management

#### List all products

```
GET /products
```

**Response**

```json
[
  {
    "id": "6891901667358720",
    "name": "Example Product",
    "description": "This is an example product",
    "price": "19.99",
    "stock": 100,
    "createdAt": "2023-01-01T12:00:00+00:00",
    "updatedAt": null
  },
  ...
]
```

#### Get a specific product

```
GET /products/{id}
```

**Response**

```json
{
  "id": "6891901667358720",
  "name": "Example Product",
  "description": "This is an example product",
  "price": "19.99",
  "stock": 100,
  "createdAt": "2023-01-01T12:00:00+00:00",
  "updatedAt": null
}
```

#### Create a product

```
POST /products
```

**Request Body**

```json
{
  "name": "New Product",
  "description": "This is a new product",
  "price": "29.99",
  "stock": 50
}
```

**Response**

```json
{
  "id": "6891901667358721",
  "name": "New Product",
  "description": "This is a new product",
  "price": "29.99",
  "stock": 50,
  "createdAt": "2023-01-02T12:00:00+00:00",
  "updatedAt": null
}
```

#### Update a product

```
PUT /products/{id}
```

**Request Body**

```json
{
  "name": "Updated Product",
  "description": "This is an updated product",
  "price": "39.99",
  "stock": 75
}
```

**Response**

```json
{
  "id": "6891901667358720",
  "name": "Updated Product",
  "description": "This is an updated product",
  "price": "39.99",
  "stock": 75,
  "createdAt": "2023-01-01T12:00:00+00:00",
  "updatedAt": "2023-01-03T12:00:00+00:00"
}
```

#### Delete a product

```
DELETE /products/{id}
```

**Response**

Status: 204 No Content

#### List products with low stock

```
GET /products/low-stock?threshold=10
```

**Response**

```json
[
  {
    "id": "6891901667358722",
    "name": "Low Stock Product",
    "description": "This product has low stock",
    "price": "49.99",
    "stock": 5,
    "createdAt": "2023-01-04T12:00:00+00:00",
    "updatedAt": null
  },
  ...
]
```

### Snowflake ID Information

#### Get Snowflake ID Info

```
GET /products/snowflake-info
```

**Response**

```json
{
  "id": "6891901667358723",
  "timestamp": 1641297600000,
  "date": "2022-01-04 12:00:00.000",
  "node_id": 1,
  "sequence": 0,
  "info": {
    "timestamp_bits": 41,
    "node_id_bits": 10,
    "sequence_bits": 12
  },
  "generated_at": "2023-01-05 12:00:00.000"
}
```

## Error Responses

The API returns appropriate HTTP status codes and error messages:

### 400 Bad Request

```json
{
  "errors": {
    "name": "This value should not be blank."
  }
}
```

### 404 Not Found

```json
{
  "message": "Product not found"
}
```

## Snowflake ID Format

Snowflake IDs are 64-bit integers presented as strings with the following structure:

- 41 bits: Timestamp (milliseconds since custom epoch, default: Jan 1, 2020)
- 10 bits: Node ID (0-1023)
- 12 bits: Sequence number (0-4095 per millisecond)

This format allows:
- 69 years of unique timestamps
- 1,024 distinct node IDs
- 4,096 IDs per millisecond per node

Benefits:
- Sortable by creation time
- No coordination required between nodes
- Guaranteed uniqueness with proper node ID allocation