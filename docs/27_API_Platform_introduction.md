1. Забираем последний релиз API Platform из https://github.com/api-platform/api-platform/releases
1. Распаковываем архив
1. Меняем пароль на БД:
    1. В файле `docker-compose.yml` меняем пароль на БД в секции `services.db.environment`
    1. В файле `api/.env` устанавливаем тот же пароль в переменной `DATABASE_URL`
1. В файле `api/.env` добавляем переменную `SHELL_VERBOSITY=-1`
1. Запускаем контейнеры командой `docker-compose up -d`
1. Заходим по адресу https://localhost, соглашаемся на невалидный сертификат
1. Проверяем работоспособность документации API и панели администрирования
1. Добавляем класс `App\Entity\Person`
    ```php
    <?php
    
    namespace App\Entity;
    
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;
    
    /**
     * @ORM\MappedSuperclass
     */
    class Person
    {
        /**
         * @var string
         *
         * @ORM\Column(type="string", length=32, nullable=false)
         * @Assert\NotBlank()
         * @Assert\Length(max=32)
         */
        public $firstName;
    
        /**
         * @var string
         *
         * @ORM\Column(type="string", length=32, nullable=false)
         * @Assert\NotBlank()
         * @Assert\Length(max=32)
         */
        public $lastName;
    
        /**
         * @var integer
         *
         * @ORM\Column(type="integer", nullable=false)
         * @Assert\NotBlank()
         * @Assert\Range(min=0)
         */
        public $age;
    }
    ```
1. Добавляем класс `App\Entity\Student`
    ```php
    <?php
   
    namespace App\Entity;
   
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\ORM\Mapping as ORM;
   
    /**
     * @ApiResource
     * @ORM\Entity
     */
    class Student extends Person
    {
        /**
         * @ORM\Column(name="id", type="bigint", unique=true)
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;
   
        /**
         * @var Teacher|null
         *
         * @ORM\ManyToOne(targetEntity="Teacher", fetch="LAZY")
         * @ORM\JoinColumns({
         *     @ORM\JoinColumn(name="teacher_id", referencedColumnName="id", nullable=true)
         * })
         */
        public $teacher;
   
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
1. Добавляем класс `App\Entity\Teacher`
    ```php
    <?php
   
    namespace App\Entity;
   
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
   
    /**
     * @ApiResource
     * @ORM\Entity
     */
    class Teacher extends Person
    {
        /**
         * @ORM\Column(name="id", type="bigint", unique=true)
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;
   
        /**
         * @var Collection|Student[]
         *
         * @ORM\OneToMany(targetEntity="Student", fetch="LAZY", mappedBy="teacher")
         */
        public $students;
   
        public function __construct()
        {
            $this->students = new ArrayCollection();
        }
   
  
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
1. Обновляем `docker-compose exec php bin/console doctrine:schema:update --force`
1. Заходим в панель администрирования и проверяем работоспособность новых сущностей
1. Исправляем аннотации в классе `App\Entity\Person`
    ```php
    <?php
   
    namespace App\Entity;
   
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
    use Symfony\Component\Validator\Constraints as Assert;
   
    /**
     * @ORM\MappedSuperclass
     */
    class Person
    {
        /**
         * @var string
         *
         * @ORM\Column(type="string", length=32, nullable=false)
         * @Assert\NotBlank()
         * @Assert\Length(max=32)
         * @Groups({"student:get","teacher:get"})
         */
        public $firstName;
   
        /**
         * @var string
         *
         * @ORM\Column(type="string", length=32, nullable=false)
         * @Assert\NotBlank()
         * @Assert\Length(max=32)
         * @Groups({"student:get","teacher:get"})
         */
        public $lastName;
   
        /**
         * @var integer
         *
         * @ORM\Column(type="integer", nullable=false)
         * @Assert\NotBlank()
         * @Assert\Range(min=0)
         * @Groups({"student:get"})
         */
        public $age;
    }
    ```
1. Исправляем аннотации в классе `App\Entity\Student`
    ```php
    <?php
   
    namespace App\Entity;
   
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
   
    /**
     * @ApiResource(
     *     normalizationContext={"groups"={"student:get"}}
     * )
     * @ORM\Entity
     */
    class Student extends Person
    {
        /**
         * @ORM\Column(name="id", type="bigint", unique=true)
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;
   
        /**
         * @var Teacher|null
         *
         * @ORM\ManyToOne(targetEntity="Teacher", fetch="LAZY")
         * @ORM\JoinColumns({
         *     @ORM\JoinColumn(name="teacher_id", referencedColumnName="id", nullable=true)
         * })
         * @Groups({"student:get"})
         */
        public $teacher;
   
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
1. Исправляем аннотации в классе `App\Entity\Teacher`
    ```php
    <?php
   
    namespace App\Entity;
   
    use ApiPlatform\Core\Annotation\ApiResource;
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Serializer\Annotation\Groups;
   
    /**
     * @ApiResource(
     *     normalizationContext={"groups"={"teacher:get"}}
     * )
     * @ORM\Entity
     */
    class Teacher extends Person
    {
        /**
         * @ORM\Column(name="id", type="bigint", unique=true)
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;
   
        /**
         * @var Collection|Student[]
         *
         * @ORM\OneToMany(targetEntity="Student", fetch="LAZY", mappedBy="teacher")
         * @Groups({"teacher:get"})
         */
        public $students;
   
        public function __construct()
        {
            $this->students = new ArrayCollection();
        }
   
        public function getId(): int
        {
            return (int)$this->id;
        }
    }
    ```
1. Видим, что возраст теперь отображается только для студентов, но не отображаются ссылки.
1. Исправляем ссылки, заменим fetch на "EAGER" в аннотациях к полям `Teacher::students` и `Student::teacher`, но видим,
что ссылки перестали работать и превратились в строки.
1. Убираем аннотации в классе `App\Entity\Person`
    ```php
    <?php
   
    namespace App\Entity;
   
    use Doctrine\ORM\Mapping as ORM;
    use Symfony\Component\Validator\Constraints as Assert;
   
    /**
     * @ORM\MappedSuperclass
     */
    class Person
    {
        /**
         * @var string
         *
         * @ORM\Column(type="string", length=32, nullable=false)
         * @Assert\NotBlank()
         * @Assert\Length(max=32)
         */
        public $firstName;
   
        /**
         * @var string
         *
         * @ORM\Column(type="string", length=32, nullable=false)
         * @Assert\NotBlank()
         * @Assert\Length(max=32)
         */
        public $lastName;
   
        /**
         * @var integer
         *
         * @ORM\Column(type="integer", nullable=false)
         * @Assert\NotBlank()
         * @Assert\Range(min=0)
         */
        public $age;
    }
    ```
1. Добавляем методы с аннотациями в классе `App\Entity\Student`
    ```php
    /**
     * @Groups({"student:get"})
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @Groups({"student:get"})
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @Groups({"student:get"})
     */
    public function getAge(): int
    {
        return $this->age;
    }
    ```
1. Добавляем методы с аннотациями в классе `App\Entity\Teacher`
    ```php
    /**
     * @Groups({"teacher:get"})
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @Groups({"teacher:get"})
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }
    ```
1. Видим, что ссылки исправились и возраст учителя не отображается.
1. В файле `App\Entity\Person` добавляем аннотацию на поле `$firstName`
    ```php
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=32, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=32)
     * @ApiProperty(iri="http://schema.org/name")
     */
    public $firstName;
    ```
1. Видим, что в ссылках теперь отображаются имена
1. В классе `App\Entity\Student`
    1. исправляем связь с классом `App\Entity\Teacher` на many-to-many
        ```php
        /**
         * @var Collection|Teacher[]
         *
         * @ORM\ManyToMany(targetEntity="Teacher", fetch="LAZY", inversedBy="students")
         * @ORM\JoinTable(name="student_teacher",
         *     joinColumns={@ORM\JoinColumn(name="student_id", referencedColumnName="id")},
         *     inverseJoinColumns={@ORM\JoinColumn(name="teacher_id", referencedColumnName="id")}
         * )
         * @Groups({"student:get"})
         */
        public $teachers;
        ```
    1. добавляем конструктор
        ```php
        public function __construct()
        {
            $this->teachers = new ArrayCollection();
        }
        ```
    1. добавляем методы
        ```php
        /**
         * @return Teacher[]
         */
        public function getTeachers(): array
        {
            return $this->teachers->getValues();
        }
   
        public function addTeacher(Teacher $teacher): void
        {
            if ($this->teachers->contains($teacher)) {
                return;
            }
            $this->teachers->add($teacher);
            $teacher->addStudent($this);
        }
   
        public function removeTeacher(Teacher $teacher): void
        {
            if (!$this->teachers->contains($teacher)) {
                return;
            }
            $this->teachers->removeElement($teacher);
            $teacher->removeStudent($this);
        }
        ```
1. В классе `App\Entity\Teacher`
    1. исправляем связь с классом `App\Entity\Student` на many-to-many
        ```php
        /**
         * @var Collection|Student[]
         *
         * @ORM\ManyToMany(targetEntity="Student", fetch="LAZY", mappedBy="teachers")
         * @Groups({"teacher:get"})
         */
        public $students;
        ```
    1. добавляем методы
        ```php
        /**
         * @return Student[]
         */
        public function getStudents(): array
        {
            return $this->students->getValues();
        }
   
        public function addStudent(Student $student): void
        {
            if ($this->students->contains($student)) {
                return;
            }
            $this->students->add($student);
            $student->addTeacher($this);
        }
   
        public function removeStudent(Student $student): void
        {
            if (!$this->students->contains($student)) {
                return;
            }
            $this->students->removeElement($student);
            $student->removeTeacher($this);
        }
        ```
1. Выполняем команду `docker-compose exec php bin/console doctrine:schema:update --force`
1. Добавляем класс `App\Annotations\Extra`
    ```php
    <?php
   
    namespace App\Annotations;
   
    /**
     * @Annotation
     * @Target({"CLASS"})
     */
    class Extra
    {
        /**
         * @var string
         * @Required
         */
        public $value;
   
        /** @var int */
        public $number;
    }
    ```
1. В классе `App\Entity\Student`
    1. Добавляем аннотацию к классу
        ```php
        /**
         * @ApiResource(
         *     normalizationContext={"groups"={"student:get"}}
         * )
         * @ORM\Entity
         * @Extra(value="Student",number=3)
         */
        ```
    1. Добавляем метод `getAnnotation`
        ```php
        /**
         * @Groups({"student:get"})
         */
        public function getAnnotation(): string
        {
            $reflectionClass = new \ReflectionClass(self::class);
            $reader = new AnnotationReader();
            $extraAnnotation = $reader->getClassAnnotation($reflectionClass, Extra::class);

            return $extraAnnotation->value.'$'.($extraAnnotation->number ?? 0);
        }
        ```
1. Видим в списке студентов параметры нашей аннотации
