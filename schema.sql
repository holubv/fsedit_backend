-- we don't know how to generate schema fsedit (class Schema) :(
create or replace table if not exists users
(
  id       int auto_increment
    primary key,
  email    varchar(64) not null,
  password varchar(64) null,
  constraint users_email_uindex
  unique (email)
);

create or replace table if not exists sessions
(
  user_id int                                   not null,
  token   char(64)                              not null,
  created timestamp default current_timestamp() not null,
  constraint sessions_users_id_fk
  foreign key (user_id) references users (id)
    on delete cascade
);

create index sessions_token_index
  on sessions (token);

create or replace table if not exists workspaces
(
  id         int auto_increment
    primary key,
  user_id    int                                   null,
  hash       char(14)                              not null,
  created    timestamp default current_timestamp() not null,
  private    tinyint default 0                     null,
  edit_token char(40)                              null,
  constraint workspaces_hash_uindex
  unique (hash),
  constraint workspaces_users_id_fk
  foreign key (user_id) references users (id)
    on delete cascade
);

create or replace table if not exists file_tree
(
  id           int auto_increment
    primary key,
  workspace_id int         not null,
  lft          int         not null,
  rgt          int         not null,
  parent_id    int         null,
  level        int         null,
  name         varchar(64) null,
  file         char(40)    null,
  constraint file_tree_file_uindex
  unique (file),
  constraint file_tree_workspaces_id_fk
  foreign key (workspace_id) references workspaces (id)
    on delete cascade
);

create index file_tree_level_index
  on file_tree (level);

create index file_tree_lft_index
  on file_tree (lft);

create index file_tree_parent_id_index
  on file_tree (parent_id);

create index file_tree_rgt_index
  on file_tree (rgt);

