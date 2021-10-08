1. Для работы потребуется утилита GNU Make https://www.gnu.org/software/make/
1. Создаём проект командой `composer create-project symlex/symlex otus_project`
1. В файле `app/web.php` заменяем
    ```php
    use Zend\Diactoros\ResponseFactory;
    use Zend\Diactoros\ServerRequestFactory;
    use Zend\Diactoros\StreamFactory;
    use Zend\Diactoros\UploadedFileFactory;
    ```
   на
    ```php
    use Laminas\Diactoros\ResponseFactory;
    use Laminas\Diactoros\ServerRequestFactory;
    use Laminas\Diactoros\StreamFactory;
    use Laminas\Diactoros\UploadedFileFactory;
    ```
1. В Dockerfile заменяем `nodejs-npm` на `npm`
1. В Makefile заменяем `cd frontend && npm install --silent && npm audit fix` на
   `cd frontend && npm install --silent --no-audit`
1. Запускаем контейнер из директории проекта командой `docker-compose up -d`
1. Выполняем команду `make terminal` для входа в контейнер
1. Выполняем команду `make all database` для сборки фронтенда и создания БД
1. Заходим на http://localhost:8081 и логинимся с реквизитами admin@example.com / passwd
1. Заходим в раздел User Management
1. Создадим новую миграцию командой `app/console migrations:generate`
1. Исправим созданную миграцию, добавив новое поле в таблицу `users`
    ```php
    <?php declare(strict_types=1);
   
    namespace DoctrineMigrations;
   
    use Doctrine\DBAL\Schema\Schema;
    use Doctrine\Migrations\AbstractMigration;
   
    /**
     * Auto-generated Migration: Please modify to your needs!
     */
    final class Version20201117151328 extends AbstractMigration
    {
        public function up(Schema $schema) : void
        {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE `users` ADD COLUMN `userAge` INTEGER');
            $this->addSql('UPDATE `users` SET `userAge` = 18');
            $this->addSql('ALTER TABLE `users` MODIFY `userAge` INTEGER NOT NULL');
        }
   
        public function down(Schema $schema) : void
        {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE `users` DROP COLUMN `userAge`');
        }
    }
    ```
1. В файле `app/db/fixtures/users.php` добавляем поле `userAge` в обе записи
1. В файле `frontend/src/pages/users.vue` добавляем колонку
    ```vue
    <template>
        <div class="page-users">
            <v-toolbar dark flat color="grey">
                <v-toolbar-title>User Management</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn fab flat @click="$refs.list.showCreateDialog()">
                    <v-icon class="addUser">add</v-icon>
                </v-btn>
            </v-toolbar>
   
            <div class="pa-4">
                <app-result-table
                        ref="list"
                        :query="query"
                        :actions="actions"
                        :model="model"
                        :columns="columns"
                >
                </app-result-table>
            </div>
        </div>
    </template>
   
    <script>
        import User from 'model/user';
   
        export default {
            name: 'page-users',
            data() {
                return {
                    query: {},
                    model: User,
                    columns: [
                        {value: "userId", text: "ID"},
                        {value: "userEmail", text: "E-Mail"},
                        {value: "userFirstName", text: "First Name"},
                        {value: "userLastName", text: "Last Name"},
                        {value: "userAge", text: "Age"},
                    ],
                    actions: [
                        {name: "delete", label: "Delete"},
                        {name: "edit", label: "Edit"},
                    ]
                };
            }
        };
    </script>
    ```
1. Ещё раз собираем приложение командой `make all database`
1. Обновляем страницу, видим, что появилась новая колонка с возрастом, но в форме редактирования её ещё нет
1. Исправляем файл `frontend/src/component/app-form-fields.vue`
    ```vue
    <template>
        <div class="app-form">
            <template v-for="(field, fieldName) in form.getDefinition()">
                <v-form>
                    <input v-if="field.hidden" type="hidden" :name="fieldName" :value="field.value" />
                    <div v-else-if="field.image" class="image">
                        <img class="mt-1" :src="field.value" />
                    </div>
                    <v-text-field v-else-if="field.type === 'int'" :key="fieldName" :label="field.caption" :required="field.required" type="number" v-model="field.value">
                    </v-text-field>
                    <v-text-field v-else-if="field.password && field.type === 'string'" :key="fieldName" :label="field.caption" :required="field.required" type="password" v-model="field.value">
                    </v-text-field>
                   <template v-else-if="field.type !== 'bool'">
                        <template v-if="field.options">
                            <v-select v-if="field.type == 'string' && field.options" :required="field.required" :label="field.caption"
                                        v-model="field.value" :readonly="field.readonly" :items="field.options" item-text="label" item-value="option">
                             </v-select>
                        </template>
                        <template v-else>
                            <v-text-field v-if="field.type == 'email' || field.type === 'string'" :required="field.required" v-model="field.value" :readonly="field.readonly" :label="field.caption"></v-text-field>
   
                        </template>
                    </template>
                    <v-checkbox v-else-if="field.type === 'bool'" :id="field.uid" v-model="field.value" :label="field.caption"></v-checkbox>
                </v-form>
            </template>
        </div>
    </template>
   
    <script>
        export default {
            name: 'app-form-fields',
            props: {
                form: {
                    type: Object
                }
            },
        };
    </script>
    ```
1. Добавляем поле в класс `App\Form\User\EditForm`
    ```php
    <?php
   
    namespace App\Form\User;
   
    use App\Form\FormAbstract;
   
    /**
     * @see http://docs.symlex.org/en/latest/input-validation/
     */
    class EditForm extends FormAbstract
    {
        protected function init(array $params = array())
        {
            $definition = [
                'userFirstName' => [
                    'caption' => 'First Name',
                    'type' => 'string',
                    'min' => 2,
                    'max' => 64,
                    'required' => true,
                ],
                'userLastName' => [
                    'caption' => 'Last Name',
                    'type' => 'string',
                    'min' => 2,
                    'max' => 64,
                    'required' => true,
                ],
                'userAge' => [
                    'caption' => 'Age',
                    'type' => 'int',
                    'min' => 0,
                    'required' => true,
                ],
                'userEmail' => [
                    'caption' => 'E-mail',
                    'type' => 'email',
                    'max' => 127,
                    'required' => true,
                ],
                'userRole' => [
                    'caption' => 'Role',
                    'type' => 'string',
                    'default' => 'user',
                    'required' => true,
                    'options' => $this->options('roles'),
                ],
                'userNewsletter' => [
                    'caption' => 'Receive newsletter and other occasional updates',
                    'type' => 'bool',
                    'required' => false,
                ]
           ];
   
            $this->setDefinition($definition);
        }
    }
    ```
1. Собираем фронтенд командой `make all`
1. Перезагружаем страницу и проверяем, что теперь нужное поле есть в форме, и изменения сохраняются в БД
