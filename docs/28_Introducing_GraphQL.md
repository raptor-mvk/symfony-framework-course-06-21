# Внедряем GraphQL

Запускаем контейнеры командой `docker-compose up -d`

## Устанавливаем API Platform

1. Подключаемся в контейнер командой `docker exec -it php sh`. Дальнейшие команды выполняем из контейнера
1. Выполняем запрос Add user v4 из Postman-коллекции v10
1. Выполняем команду `php bin/console followers:add 1` и добавляем 30 пользователей
1. Выполняем команду `composer require api`
1. К классу `App\Entity\User` добавляем аннотацию `@ApiResource()`
1. В файле `config/routes/api_platform.yaml` меняем префикс API
    ```yaml
    api_platform:
        resource: .
        type: api_platform
        prefix: /api-platform
    ``` 
1. Проверяем, что API Platform установился, зайдя по адресу http://localhost:7777/api-platform

## Устанавливаем GraphQL

1. Устанавливаем GraphQL командой `composer require webonyx/graphql-php`
1. Заходим по адресу http://localhost:7777/api-platform/graphql
1. Делаем запрос
    ```
    {
      users {
        edges {
          node {
            id
            _id
            login
          }
        }
      }
    } 
    ```

## Пробуем различные запросы со связанными сущностями

1. В классе `App\Entity\Subscription` добавляем аннотацию `@ApiResource`
1. В классе `App\Entity\User` добавляем метод `getSubscriptionFollowers`
    ```php
    /**
     * @return Subscription[]
     */
    public function getSubscriptionFollowers(): array
    {
        return $this->subscriptionFollowers->toArray();
    }
    ```
1. Делаем запрос
    ```
    {
      users {
        edges {
          node {
            id
            _id
            login
            subscriptionFollowers {
              edges {
                node {
                  id
                }
              }
            }
          }
        }
      }
    }
    ```
1. Получаем пользователей и id их подписчиков, добавим ограничение и метаинформацию
    ```
    {
      users {
        edges {
          node {
            id
            _id
            login
            subscriptionFollowers(first: 3) {
              totalCount
              edges {
                node {
                  id
                }
                cursor
              }
              pageInfo {
                endCursor
                hasNextPage
              }
            }
          }
        }
      }
    }
    ```
1. Для получения следующей страницы добавим параметр `after`
    ```
    {
      users {
        edges {
          node {
            id
            _id
            login
            subscriptionFollowers(first: 3 after: "Mg==") {
              totalCount
              edges {
                node {
                  id
                }
                cursor
              }
              pageInfo {
                endCursor
                hasNextPage
              }
            }
          }
        }
      }
    }
    ```
1. Раскроем дополнительно информацию о подписке ещё на один уровень
    ```
    {
      users {
        edges {
          node {
            id
            _id
            login
            subscriptionFollowers(first: 3 after: "Mg==") {
              edges {
                node {
                  follower {
                    id
                    _id
                    login
                  }
                }
              }
              pageInfo {
                endCursor
                hasNextPage
              }
            }
          }
        }
      }
    }
    ```
1. Получим данные о пользователе через фильтр по id
    ```
    {
      user(id: "/api-platform/users/1") {
        id
        _id
        login
        subscriptionFollowers(first: 3, after: "Mg==") {
          edges {
            node {
              follower {
                id
                _id
                login
              }
            }
          }
          pageInfo {
            endCursor
            hasNextPage
          }
        }
      }
    }
    ```

## Добавляем фильтрацию

1. В классе `App\Entity\User` добавим аннотацию для фильтрации
   `@ApiFilter(SearchFilter::class, properties={"login":"partial"})`
1. Получим данные о пользователях с фильтром
    ```
    {
      users(login: "#1_#2") {
        edges {
          node {
            _id
            login
          }
        }
      }
    }
    ```

## Добавляем сортировку

1. В классе `App\Entity\User` добавим аннотацию для сортировки `@ApiFilter(OrderFilter::class, properties={"login"})`
1. Получим данные о пользователях с сортировкой
    ```
    {
      users(first: 5 order: { login: "DESC" }) {
        edges {
          node {
            _id
            login
          }
        }
      }
    }
    ```

## Добавляем поиск по вложенному полю

1. В классе `App\Entity\Subscription` добавим аннотацию для поиска по вложенному полю
`@ApiFilter(SearchFilter::class, properties={"follower.login":"partial"})`
1. Получим данные с фильтрацией подзапроса
    ```
    {
      users(login: "another_user") {
        edges {
          node {
            _id
            login
            subscriptionFollowers(follower_login: "#1_#2") {
              edges {
                node {
                  follower {
                    _id
                    login
                  }
                }
              }
            }
          }
        }
      }
    }
    ```

## Работаем с мутаторами

1. В классе `App\Entity\User` исправляем метод `setRoles`
    ```php
    /**
     * @param string[]|string $roles
     *
     * @throws JsonException
     */
    public function setRoles($roles): void
    {
        $this->roles = is_array($roles)? json_encode($roles, JSON_THROW_ON_ERROR) : $roles;
    }
    ```
1. Добавим пользователя запросом
    ```
    mutation CreateUser($login:String!, $password:String!, $age:Int!) {
      createUser(input:{login:$login, password:$password, age:$age, isActive:true, roles:"[]"}) {
        user {
          _id
        }
      }
    }
    ```
   с переменными
    ```php
    {
      "login":"graphql_user",
      "password": "graphql_password",
      "age": 35
    }
    ```
1. Изменим пользователя запросом
    ```
    mutation UpdateUser($id:ID!, $login:String!, $password:String!, $age:Int!) {
      updateUser(input:{id:$id, login:$login, password:$password, age:$age}) {
        user {
          _id
          login
          password
          age
        }
      }
    }
    ```
   с переменными
    ```
    {
      "id":"/api-platform/users/32",
      "login":"graphql_user",
      "password": "graphql_password",
      "age": 35
    }
    ```
1. Удалим пользователя запросом
    ```
    mutation DeleteUser($id:ID!) {
      deleteUser(input:{id:$id}) {
        user {
          _id
          login
          password
          age
        }
      }
    }
    ```
   с переменными
    ```
    {
      "id":"/api-platform/users/34"
    }
    ```
   Получим ошибку, т.к. пытаемся получить значения полей для удалённой записи.
 
## Делаем кастомный резолвер коллекций
 
1. В класс `App\Entity\User` добавим новое поле, геттер и сеттер
    ```php
    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $isProtected;

    public function isProtected(): bool
    {
        return $this->isProtected ?? false;
    }

    public function setIsProtected(bool $isProtected): void
    {
        $this->isProtected = $isProtected;
    }
    ```
1. Выполняем команды
    ```shell
    php bin/console doctrine:cache:clear-metadata
    php bin/console doctrine:migrations:diff
    php bin/console doctrine:migrations:migrate
    ```
1. Добавляем класс `App\Resolver\UserCollectionResolver`
    ```php
    <?php
   
    namespace App\Resolver;
   
    use ApiPlatform\Core\GraphQl\Resolver\QueryCollectionResolverInterface;
    use App\Entity\User;
   
    class UserCollectionResolver implements QueryCollectionResolverInterface
    {
        private const MASK = '****';
   
        /**
         * @param iterable<User> $collection
         *
         * @return iterable<User>
         */
        public function __invoke(iterable $collection, array $context): iterable
        {
            /** @var User $user */
            foreach ($collection as $user) {
                if ($user->isProtected()) {
                    $user->setLogin(self::MASK);
                    $user->setPassword(self::MASK);
                }
            }
   
            return $collection;
        }
    }
    ```
1. В классе `App\Entity\User` исправляем аннотацию `@ApiResource`
    ```php
    /**
     * @ORM\Table(name="`user`")
     * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
     * @ApiResource(
     *     graphql={
     *         "collectionQuery"={
     *             "collection_query"=UserCollectionResolver::class
     *         }
     *     }
     * )
     * @ApiFilter(SearchFilter::class, properties={"login":"partial"})
     * @ApiFilter(OrderFilter::class, properties={"login"})
     */
    ```
1. Изменяем в БД у пользователя с подписчиками значение поля `is_protected` на `true`

1. Получим данные новым запросом
    ```
    {
      collectionQueryUsers {
        edges {
          node {
            _id
            login
            password
          }
        }
      }
    }
    ```

## Делаем кастомный резолвер одной сущности

1. Добавляем класс `App\Resolver\UserResolver`
    ```php
    <?php
   
    namespace App\Resolver;
   
    use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
    use App\Entity\User;
   
    class UserResolver implements QueryItemResolverInterface
    {
        private const MASK = '****';
   
        /**
         * @param User|null $item
         */
        public function __invoke($item, array $context): User
        {
            if ($item->isProtected()) {
                $item->setLogin(self::MASK);
                $item->setPassword(self::MASK);
            }
    
            return $item;
        }
     }
    ```
1. В классе `App\Entity\User` исправляем аннотацию `@ApiResource`
    ```php
    /**
     * @ORM\Table(name="`user`")
     * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
     * @ApiResource(
     *     graphql={
     *         "itemQuery"={
     *             "item_query"=UserResolver::class
     *         },
     *         "collectionQuery"={
     *             "collection_query"=UserCollectionResolver::class
     *         }
     *     }
     * )
     * @ApiFilter(SearchFilter::class, properties={"login":"partial"})
     * @ApiFilter(OrderFilter::class, properties={"login"})
     */
    ```
1. Получим данные новым запросом
    ```
    {
      itemQueryUser(id: "/api-platform/users/1") {
        _id
        login
        password
      }
    }
    ```
 
##
 
1. Изменяем класс `App\User\UserResolver`
    ```php
    <?php
    
    namespace App\Resolver;
    
    use ApiPlatform\Core\GraphQl\Resolver\QueryItemResolverInterface;
    use App\Entity\User;
    use App\Manager\UserManager;
    
    class UserResolver implements QueryItemResolverInterface
    {
        private UserManager $userManager;
    
        public function __construct(UserManager $userManager) {
            $this->userManager = $userManager;
        }
    
        /**
         * @param User|null $item
         */
        public function __invoke($item, array $context): User
        {
            if (isset($context['args']['id'])) {
                $item = $this->userManager->findUserById($context['args']['id']);
            } elseif (isset($context['args']['login'])) {
                $item = $this->userManager->findUserByLogin($context['args']['login']);
            }
    
            return $item;
        }
    }
    ```
1. В классе `App\Entity\User` исправляем аннотацию `@ApiResource`
    ```php
    /**
     * @ORM\Table(name="`user`")
     * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
     * @ApiResource(
     *     graphql={
     *         "itemQuery"={
     *             "item_query"=UserResolver::class,
     *             "args"={
     *                 "id"={"type":"Int"},
     *                 "login"={"type":"String"}
     *             },
     *             "read"=false
     *         },
     *         "collectionQuery"={
     *             "collection_query"=UserCollectionResolver::class
     *         }
     *     }
     * )
     * @ApiFilter(SearchFilter::class, properties={"login":"partial"})
     * @ApiFilter(OrderFilter::class, properties={"login"})
     */
    ```
1. Получим данные новым запросом
    ```
    {
      itemQueryUser(login: "another_user") {
        _id
        login
        password
      }
    }
    ```
1. Получим данные новым запросом
    ```
    {
      itemQueryUser(id: 1) {
        _id
        login
        password
      }
    }
    ```
