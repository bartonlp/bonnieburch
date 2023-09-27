delete from teams where team in('2,4,10,11');
insert into teams (team, name1, name2, email1, email2, created, lasttime) values
(2, 'Dru Wilkins', 'Ken Wilkins', 'wilkins.dru@gmail.com', 'kwilkins262@gmail.com', now(), now()),
(4, 'Carole McCoy', 'John McCoy', 'c.j.mccoy62@gmail.com', 'j.c.mccoy62@gmail.com', now(), now()),
(10,'Janet Swain', 'Phil Swain', 'janetbenrey@gmail.com', 'pswain102@gmail.com', now(), now()),
(11,'Bonnie Burch', 'Pam Duling', 'bonnieburch2015@gmail.com', 'pamduling@hotmail.com', now(), now());
