Тестовое задание по обработке таблицы звонков
==============

Модификация таблицы
--------------
Все команды для sql лежат в фйле testtel.sql, здесь небольшое пояснение.

- Создана база, пользователь с паролем

```MySQL
    DROP DATABASE IF EXISTS testtel;
    CREATE DATABASE testtel;
    GRANT ALL PRIVILEGES ON testtel.* TO 'teluser'@'%' IDENTIFIED BY 'telpass' WITH GRANT OPTION;
    GRANT ALL PRIVILEGES ON testtel.* TO 'teluser'@'localhost' IDENTIFIED BY 'telpass' WITH GRANT OPTION;
```

- Импортированы данные
- Преобразована таблица, чтобы поменьше полей было - там много пустых

```MySQL
    Use testtel;
    Alter Table systemcall Drop Index audio,  Drop Index `to`, Drop Index `from`, Drop Index `set`,
    Drop audio, Drop `to`, Drop `from`, Drop `set`, Drop anketa_id, Drop status, Drop callerid, Drop comments, Drop owner_id;

    Create Table tel (
      t_id int NOT NULL AUTO_INCREMENT,
      t_num varchar(16) DEFAULT '',
      PRIMARY KEY (t_id),
      KEY t_num (t_num)
    ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

    Create Table oper (
      o_id int NOT NULL AUTO_INCREMENT,
      o_name varchar(16) DEFAULT '',
      PRIMARY KEY (o_id)
    ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci;

    Insert into tel (t_num) SELECT Distinct `gateway` As t_num  FROM systemcall Where gateway <> '';

    Alter Table systemcall Add `tel_id` int;
    UPDATE systemcall, tel SET systemcall.tel_id=tel.t_id WHERE systemcall.gateway=tel.t_num;

    Insert into oper (o_name) SELECT Distinct `type` As o_name FROM systemcall Where type <> '';
    Alter Table systemcall Add `oper_id` int;
    UPDATE systemcall, oper SET systemcall.oper_id=oper.o_id WHERE systemcall.type=oper.o_name;
```


php код
--------------
Для удобства отображения написана пара функций, чтобы выводить шаблоны и json.
Для передачи на клиент использовал разбиение всего диапазона дат на 50 интервалов, чтобы не мучить клиента.
Константа в файле index.php стоит, поменять можно.
Подключение к базе лежит в файле dp.php - опечатался, но сейчас менять не буду, для желающих - константа в файле index.php есть.

js код
--------------
Поскольку на jscript ограничений нет, то взял bootstrap для грида, думал, может еще чего из него использовать, но не стал заморачиваться.
Взял jQuery для datepicker и ajax.
Взял d3 для рисования графиков.

Вот и все. Сделал несколько разных выборок, можно по примеру построить еще.

