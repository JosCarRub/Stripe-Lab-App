# StripeLabApp - Aplicación de Prueba para Integración con Stripe

StripeLabApp es una aplicación PHP de prueba diseñada para demostrar y aprender cómo integrar los flujos de pago único y suscripciones de Stripe. Permite registrar transacciones en una base de datos, visualizar recibos/facturas y gestionar el estado de las suscripciones.

## Características Principales

*   **Flujo de Pago Único:** Implementación completa para iniciar un pago único a través de Stripe Checkout.
*   **Flujo de Suscripciones:** Implementación para iniciar suscripciones a diferentes planes (mensual, anual) mediante Stripe Checkout.
*   **Manejo de Webhooks de Stripe:** Procesamiento de eventos clave de Stripe para:
    *   Crear y actualizar suscripciones (`customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`).
    *   Registrar pagos de facturas de suscripción (`invoice.paid`).
    *   Registrar pagos únicos (`payment_intent.succeeded`).
    *   Enriquecer datos con información de cargos (`charge.succeeded`).
    *   Actualizar información del cliente (`customer.created`, `customer.updated`).
    *   Confirmar sesiones de checkout (`checkout.session.completed`).
*   **Persistencia en Base de Datos:** Registro de transacciones y suscripciones en una base de datos MySQL.
*   **Visualización de Datos:**
    *   Página para listar todas las facturas/recibos del sistema con paginación.
    *   Página para listar todas las suscripciones del sistema con paginación y la opción de buscar por cliente.
    *   Capacidad de ver URLs de facturas y recibos alojados por Stripe.
*   **Gestión de Suscripciones (Básica):** Funcionalidad para cancelar suscripciones (inmediatamente o al final del periodo) desde la vista de suscripciones.
*   **Arquitectura por Capas:** Sigue un diseño `Controller -> Service -> Strategy -> Mapper/Factory -> Repository` para una mejor organización y mantenibilidad.
*   **Logging Detallado:** Múltiples archivos de log para rastrear eventos de la aplicación, errores, payloads de Stripe y consultas a la base de datos.
*   **Interfaz de Usuario:** Páginas HTML/PHP con Bootstrap 5 para la selección de planes, inicio de pagos y visualización de datos.

## Requisitos Previos

*   PHP 8.0.0 o superior.
*   Composer para la gestión de dependencias.
*   Servidor web (Apache, Nginx, o el servidor embebido de PHP).
*   MySQL (u otra base de datos compatible con PDO, con ajustes menores en las consultas de creación de tablas).
*   Una cuenta de Stripe y acceso a tus claves API (publicable y secreta) y secreto de webhook.
*   Stripe CLI (opcional pero muy recomendado para probar webhooks localmente).
*   Docker y Docker Compose (opcional, si usas el `docker-compose.yml` proporcionado para la base de datos).

## Instalación

1.  **Clonar el Repositorio:**
    ```bash
    git clone https://github.com/tu-usuario/StripeLabApp.git
    cd StripeLabApp
    ```

2.  **Instalar Dependencias PHP:**
    ```bash
    composer install
    ```

3.  **Configurar Variables de Entorno:**
    Crea un archivo `.env` en la raíz del proyecto con la siguiente estructura:

    ```env
    # Claves de Stripe (obtenidas de tu Dashboard de Stripe en modo de prueba)
    STRIPE_PUBLISHABLE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxx
    STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxxxxxxxxxxxx
    STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxx # Secreto del endpoint de webhook

    # IDs de Precio de Stripe (Lookup Keys o IDs directos de tus precios en Stripe)
    STRIPE_PRICE_LOOKUP_KEY_MONTHLY=monthly_subscriptions_lookup_key
    STRIPE_PRICE_LOOKUP_KEY_YEARLY=annual_payment_lookup_key
    STRIPE_PRICE_LOOKUP_KEY_ONE_TIME=one_payment_lookup_key

    # (Opcional) Precios para mostrar en el frontend (si no quieres hardcodearlos)
    PRICE_DISPLAY_MONTHLY="3,00 €"
    PRICE_DISPLAY_YEARLY="15,00 €"
    PRICE_DISPLAY_ONE_TIME="10,00 €"

    # Configuración de la Base de Datos
    DB_HOST=127.0.0.1 # O el nombre del servicio Docker si PHP corre fuera de Docker
    DB_PORT=3307     # El puerto que tu MySQL está escuchando
    DB_DATABASE=stripe_lab
    DB_USER=xxxxxxx
    DB_PASSWORD=xxxxx

    # Configuración de la Aplicación
    APP_DOMAIN=http://localhost:8000 # URL base de tu aplicación para redirecciones de Stripe
    ```
    **Importante:**
    *   Reemplaza los valores `xxxxxxxxxx` con tus claves reales de Stripe (modo de prueba).
    *   Crea los Productos y Precios correspondientes en tu Dashboard de Stripe y usa sus "Lookup keys" o IDs de Precio en las variables `STRIPE_PRICE_LOOKUP_KEY_...`.

4.  **Configurar la Base de Datos:**
    *   **Usando Docker (Recomendado para desarrollo fácil):**
        El archivo `database/docker/docker-compose.yml` define un servicio MySQL.
        Desde el directorio `database/docker/`, ejecuta:
        ```bash
        docker-compose up -d
        ```
        Esto levantará un contenedor MySQL con las credenciales y base de datos especificadas en el `docker-compose.yml`.
    *   **Manualmente:**
        Si no usas Docker, asegúrate de tener un servidor MySQL corriendo y crea una base de datos (ej. `stripe_lab`) y un usuario con los permisos necesarios, coincidiendo con tu archivo `.env`.
    *   **Crear Tablas:** Ejecuta las sentencias SQL que se encuentran en `database/tables.txt` en tu base de datos `stripe_lab` para crear las tablas `StripeTransactions` y `StripeSubscriptions`.

5.  **Iniciar el Servidor PHP:**
    Desde la raíz de tu proyecto, puedes usar el servidor embebido de PHP (asegúrate de que `public/` sea tu document root):
    ```bash
    php -S localhost:8000 -t public
    ```
    Ahora deberías poder acceder a la aplicación en `http://localhost:8000`.

### Configurar Webhook de Stripe

Para que Stripe envíe eventos a tu aplicación localmente, necesitas usar Stripe CLI:

1.  **Inicia sesión en Stripe CLI:**
    ```bash
    stripe login
    ```

2.  **Escucha y reenvía eventos a tu endpoint de webhook local:**
    Asegúrate de que tu servidor PHP local esté corriendo (ej. en `localhost:8000`).
    ```bash
    stripe listen --forward-to http://localhost:8000/public/v1/webhook.php
    ```
    Stripe CLI te proporcionará un **secreto de webhook** (ej. `whsec_...`). **Copia este secreto y pégalo en tu archivo `.env` para la variable `STRIPE_WEBHOOK_SECRET`.** ¡Debes usar este secreto específico mientras `stripe listen` esté activo!

3.  **Prueba enviando un evento:**
    En otra terminal, puedes disparar eventos de prueba:
    ```bash
    stripe trigger payment_intent.succeeded
    stripe trigger customer.subscription.created
    # ... y otros eventos que quieras probar
    ```

## Flujo de Pago

1.  **Página de Inicio (`index.php` o `single-payment.php` / `subscriptions-payment.php`):** El usuario selecciona un plan de pago único o un plan de suscripción.
2.  **JavaScript Frontend:** Al hacer clic en un botón de pago/suscripción, se hace una solicitud `fetch` a un endpoint PHP en `public/v1/` (ej. `create_payment_session.php` o `create_subscription_session.php`).
3.  **Endpoint PHP de Creación de Sesión:**
    *   Este script utiliza `StripeCheckoutSessionServiceImpl`.
    *   El servicio obtiene el ID del Precio de Stripe usando la `lookup_key` proporcionada.
    *   Crea una Sesión de Checkout de Stripe (`mode: 'payment'` o `mode: 'subscription'`) con las URLs de éxito y cancelación.
    *   Devuelve el ID de la Sesión de Checkout al frontend como JSON.
4.  **Redirección a Stripe Checkout:** El JavaScript del frontend usa el ID de la sesión para redirigir al cliente a la página de pago segura de Stripe.
5.  **Completar Pago/Suscripción en Stripe:** El cliente introduce sus datos de pago.
6.  **Redirección de Vuelta:** Stripe redirige al cliente a la `success_url` o `cancel_url` configurada.
7.  **Webhooks de Stripe:** Simultáneamente, Stripe envía una serie de eventos de webhook a tu endpoint `public/v1/webhook.php`.
    *   `checkout.session.completed`
    *   `payment_intent.succeeded` (para pagos)
    *   `invoice.paid` (para facturas de suscripción)
    *   `customer.subscription.created` (para nuevas suscripciones)
    *   Y otros eventos relevantes.
8.  **Procesamiento de Webhooks:**
    *   `StripeWebhookControllerImpl` recibe el webhook.
    *   `StripeWebhookServiceImpl` verifica la firma, parsea el evento y selecciona la `Strategy` apropiada.
    *   La `Strategy` usa `Mappers` para convertir el payload de Stripe a DTOs, `Factories` para crear Entidades/Models, y `Repositories` para guardar/actualizar los datos en la base de datos.

## Panel de Control (Vistas)

*   **`public/index.php`**: Página principal para seleccionar planes de pago único o suscripción.
*   **`public/single-payment.php`**: Detalle y botón para el pago único.
*   **`public/subscriptions-payment.php`**: Detalle y botones para los planes de suscripción.
*   **`public/invoices.php`**: Muestra una lista de todas las facturas y recibos registrados en el sistema, con paginación. Permite buscar facturas por ID de cliente.
*   **`public/view-subscriptions.php`**: Muestra una lista de todas las suscripciones del sistema y permite buscar suscripciones por ID de cliente. Incluye botones para gestionar (cancelar) suscripciones.
*   **`public/success.html` y `public/cancel.html`**: Páginas simples de redirección después del checkout.

## Eventos de Stripe Soportados (Estrategias Implementadas)

La aplicación actualmente maneja los siguientes eventos de webhook a través de estrategias dedicadas:

*   `checkout.session.completed` (`CheckoutSessionCompletedStrategyImpl`)
*   `customer.created` (`CustomerCreatedOrUpdatedStrategyImpl`)
*   `customer.updated` (`CustomerCreatedOrUpdatedStrategyImpl`)
*   `payment_intent.succeeded` (`PaymentIntentSucceededStrategyImpl`)
*   `charge.succeeded` (`ChargeSucceededStrategyImpl`)
*   `customer.subscription.created` (`SubscriptionCreatedStrategyImpl`)
*   `customer.subscription.updated` (`SubscriptionUpdatedStrategyImpl`)
*   `customer.subscription.deleted` (`SubscriptionDeletedStrategyImpl`)
*   `invoice.paid` (`InvoicePaidStrategyImpl`)

Otros eventos son recibidos y logueados por `StripePayloadLogger` y, si no tienen estrategia, por `UnhandledStripeEventLogger`.

## Tarjetas de Prueba de Stripe

Para probar la aplicación sin realizar pagos reales, utiliza las siguientes tarjetas de prueba de Stripe (puedes usar cualquier fecha de expiración futura y cualquier CVC de 3 dígitos):

*   **Pago Exitoso:** `4242` `4242` `4242` `4242`
*   **Pago Exitoso (Requiere Autenticación 3D Secure):** `4000` `0025` `0000` `3155` (Sigue las instrucciones en la página de Stripe para completar la autenticación).
*   **Pago Rechazado (Fondos Insuficientes):** `4000` `0000` `0000` `9995`
*   **Pago Rechazado (Genérico):** `4000` `0000` `0000` `0002`
*   Consulta la [documentación de Stripe sobre tarjetas de prueba](https://stripe.com/docs/testing#cards) para más opciones.

## Logs

La aplicación genera varios archivos de log en el directorio `logs/` (ubicado en la raíz del proyecto):

*   `events.log`: Logs generales de flujo de la aplicación y eventos importantes.
*   `errors.log`: Errores y excepciones capturadas.
*   `database.log`: Consultas SQL ejecutadas (útil para depuración).
*   `stripe_payloads.log`: Payloads JSON completos de todos los eventos de webhook de Stripe recibidos y verificados.
*   `unhandled_stripe_events.log`: Payloads JSON de eventos de Stripe para los cuales no se encontró una estrategia de manejo.


## Problemas Comunes

1.  **Webhook no recibido o error de firma:**
    *   Asegúrate de que `stripe listen --forward-to http://localhost:PUERTO/public/v1/webhook.php` esté corriendo.
    *   Verifica que la URL de reenvío sea correcta y que tu servidor PHP local esté accesible en ese puerto y ruta.
    *   Confirma que el `STRIPE_WEBHOOK_SECRET` en tu archivo `.env` coincida exactamente con el secreto `whsec_...` que te proporciona el comando `stripe listen`.
    *   Revisa los logs de `stripe listen` en la terminal para ver si hay errores al reenviar el evento.

2.  **Error de base de datos (ej. "Connection refused", "Access denied", "Table not found"):**
    *   Verifica que las credenciales de base de datos (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USER`, `DB_PASSWORD`) en tu archivo `.env` sean correctas y coincidan con tu configuración de MySQL (o Docker).
    *   Asegúrate de que el servidor MySQL esté corriendo.
    *   Confirma que las tablas `StripeTransactions` y `StripeSubscriptions` hayan sido creadas en la base de datos correcta usando las sentencias de `database/tables.txt`.

3.  **Errores "Class ... not found":**
    *   Ejecuta `composer dump-autoload` después de añadir nuevas clases o cambiar namespaces.
    *   Verifica que los `use` statements y los namespaces sean correctos en tus archivos PHP.

4.  **Errores de Stripe API (ej. "Invalid API key provided", "No such price"):**
    *   Asegúrate de que `STRIPE_SECRET_KEY` en `.env` sea tu clave secreta de prueba (`sk_test_...`).
    *   Verifica que los `STRIPE_PRICE_LOOKUP_KEY_...` en `.env` correspondan a lookup keys o IDs de Precios válidos y activos en tu Dashboard de Stripe (modo prueba).

## Desarrollo y Ampliación

Para añadir soporte para nuevos eventos de webhook de Stripe:

1.  **Crear un DTO (si es necesario):** Si el payload del nuevo evento tiene una estructura que quieres representar de forma tipada, crea un nuevo DTO en `src/Commons/DTOs/`.
2.  **Crear un Mapper (si es necesario):** Crea un nuevo Mapper en `src/Mappers/` para convertir el payload del evento de Stripe al DTO.
3.  **Crear una nueva Clase de Estrategia:**
    *   En `src/Strategy/Impl/`, crea una nueva clase, por ejemplo, `MiNuevoEventoStrategyImpl.php`.
    *   Haz que implemente `App\Strategy\StripeWebhookStrategyInterface`.
    *   Implementa los métodos `getSupportedEventType()` (devolviendo el `StripeEventTypeEnum` correspondiente al nuevo evento), `isApplicable()`, y `process()`.
    *   En `process()`, usa el Mapper para obtener el DTO, luego los Factories y Repositories necesarios para tu lógica.
4.  **Añadir el Evento a `StripeEventTypeEnum`:** Si es un nuevo tipo de evento, añádelo a `src/Commons/Enums/StripeEventTypeEnum.php`.
5.  **Registrar la Estrategia en `config/Bootstrap.php`:**
    *   En el método `getStripeStrategies()`, instancia tu nueva estrategia y añádela al array `$stripeStrategies`. Asegúrate de inyectarle las dependencias correctas (Mappers, Factories, Repositories).
