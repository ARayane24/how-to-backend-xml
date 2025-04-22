# User Account API

This document describes the API endpoints for managing user accounts in the application. The API uses XML for both requests and responses.

## Base URL

The base URL for the API endpoints should be prefixed with the API version, typically `/api/v1`. The full base URL will depend on the application's domain and configuration (e.g., `http://127.0.0.1:8000/api/v1/`).

**Content Type:** All requests and responses use the `application/xml` content type. Ensure you set the `Content-Type` and `Accept` headers accordingly.

## Endpoints

### List All Accounts

Retrieves a list of all user accounts.

- **URL:** `/user-accounts`
- **Method:** `GET`
- **Description:** Returns an XML document containing a list of all available user accounts.
- **Request Body:** None
- **Response:**

- `200 OK`:

    ```xml

          <accounts>
              <account id="1">
                  <userName>user1</userName>
                  <password>password2</password>
                  <email>user1@example.com</email>
              </account>
              <account id="2">
                  <userName>user2</userName>
                  <password>password2</password>
                  <email>user2@example.com</email>
              </account>
          </accounts>

    ```

- `404 Not Found`:

    ```xml
            <error>No accounts found</error>
    ```

### Get Single Account

Retrieves a specific user account by its ID.

- **URL:** `/user-accounts/{id}`
- **Method:** `GET`
- **Description:** Returns the XML representation of the user account with the specified ID.
- **URL Parameters:**
  - `id` (integer, required): The ID of the account to retrieve.
- **Request Body:** None
- **Response:**

  - `200 OK`:

    ```xml
            <account id="123">
                <userName>specific_user</userName>
                <password>password</password>
                <email>specific_user@example.com</email>
            </account>
    ```

  - `404 Not Found`:

    ```xml
            <error>Account not found</error>
    ```

### Create New Account

Creates a new user account.

- **URL:** `/user-accounts`
- **Method:** `POST`
- **Description:** Creates a new user account using the provided data.
- **Request Body:** An XML document containing the new account details. Must include `userName`, `password`, and `email`.

    ```xml
        <account>
            <userName>new_user</userName>
            <password>secure_password</password>
            <email>new_user@example.com</email>
        </account>
    ```

- **Response:**

  - `201 Created`: Returns the XML of the newly created account, including its assigned ID.

    ```xml
            <account id="124">
                <userName>new_user</userName>
                <password>secure_password</password>
                <email>new_user@example.com</email>
            </account>
    ```

  - `400 Bad Request`:

    ```xml
            <error>No data provided</error>

        or

            <error>Missing required fields</error>

        or

            <error>Invalid XML format</error>
    ```

  - `500 Internal Server Error`:

    ```xml
             <error>Could not create the new account</error> 
    ```

### Update Account

Updates an existing user account.

- **URL:** `/user-accounts/{id}`
- **Method:** `PUT`
- **Description:** Updates the user account with the specified ID using the provided data.
- **URL Parameters:**
  - `id` (integer, required): The ID of the account to update.
- **Request Body:** An XML document containing the fields to update. You can provide one or more fields (`userName`, `password`, `email`).

    ```xml
        <account>
            <email>updated_email@example.com</email>
            <password>new_secure_password</password>
        </account>
    ```

- **Response:**

  - `200 OK`: Returns the XML of the updated account.

    ```xml
            <account id="123">
                <userName>specific_user</userName>
                <password>new_secure_password</password>
                <email>updated_email@example.com</email>
            </account>
    ```

  - `400 Bad Request`:

    ```xml
            <error>No update data provided</error>

        or

            <error>Invalid XML format in update data</error>
    ```

  - `404 Not Found`:

    ```xml
            <error>Account not found</error>
    ```

  - `500 Internal Server Error`:

    ```xml
            <error>Failed to process update request</error>
    ```

### Delete Account

Deletes a user account.

- **URL:** `/user-accounts/{id}`
- **Method:** `DELETE`
- **Description:** Deletes the user account with the specified ID.
- **URL Parameters:**
  - `id` (integer, required): The ID of the account to delete.
- **Request Body:** None
- **Response:**

  - `204 No Content`: The account was successfully deleted. The response body is empty.
  - `404 Not Found`:

    ```xml
            <error>Account not found</error>
    ```

  - `500 Internal Server Error`:

    ```xml
            <error>Failed to process delete request</error>
    ```

## Error Responses

In case of an error, the API will return an appropriate HTTP status code and an XML response body with a root `<error>` element containing a descriptive message.

Example Error Response:

```xml
        <error>Error description goes here.</error>
```
