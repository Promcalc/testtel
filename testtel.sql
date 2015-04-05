DROP DATABASE IF EXISTS testtel;
CREATE DATABASE testtel;
GRANT ALL PRIVILEGES ON testtel.* TO 'teluser'@'%' IDENTIFIED BY 'telpass' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON testtel.* TO 'teluser'@'localhost' IDENTIFIED BY 'telpass' WITH GRANT OPTION;

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

-- Alter Table systemcall Drop `tel_id`;
Alter Table systemcall Add `tel_id` int;
UPDATE systemcall, tel SET systemcall.tel_id=tel.t_id WHERE systemcall.gateway=tel.t_num;

Insert into oper (o_name) SELECT Distinct `type` As o_name FROM systemcall Where type <> '';
Alter Table systemcall Add `oper_id` int;
UPDATE systemcall, oper SET systemcall.oper_id=oper.o_id WHERE systemcall.type=oper.o_name;

Set @np := 50;
SELECT @d1 := MIN(UNIX_TIMESTAMP(moment)), @d2 := MAX(UNIX_TIMESTAMP(moment)) FROM testtel.systemcall;
SELECT @d1, @d2, @dt := Ceil((@d2 - @d1) / @np);
Select id, moment, UNIX_TIMESTAMP(moment) As ts, floor((UNIX_TIMESTAMP(moment) - @d1) / @dt) FROM systemcall Order by id limit 7600, 200;

Select Count(*), floor((UNIX_TIMESTAMP(moment) - @d1) / @dt) As npart FROM systemcall Group By npart Order by npart;
Select Count(*), floor((UNIX_TIMESTAMP(moment) - @d1) / @dt) As npart FROM systemcall Where oper_id = 2 Group By npart Order by npart;
