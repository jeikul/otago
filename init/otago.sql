/*==============================================================*/
/* DBMS name:      MySQL 5.0                                    */
/* Created on:     2022/7/31 20:58:53                           */
/*==============================================================*/


drop table if exists tbCategory;

drop table if exists tbCategory_Food;

drop table if exists tbChannel;

drop table if exists tbDailyChannel;

drop table if exists tbDailyFood;

drop table if exists tbDailySummary;

drop table if exists tbDaily_Category;

drop table if exists tbFood;

drop table if exists tbFormula;

drop table if exists tbOrder;

drop table if exists tbOrder_Food;

drop table if exists tbRestaurant;

drop table if exists tbRestaurant_Food;

drop table if exists tbSystem;

drop table if exists tbUnit;

/*==============================================================*/
/* Table: tbCategory                                            */
/*==============================================================*/
create table tbCategory
(
   id                   int not null auto_increment,
   fdName               varchar(64),
   primary key (id)
);

alter table tbCategory comment '��Ʒ���࣬��ɳ�����h������ۡ��Ƿ���ħ��˿���ȵ�';

/*==============================================================*/
/* Table: tbCategory_Food                                       */
/*==============================================================*/
create table tbCategory_Food
(
   fdCategoryID         int not null,
   fdFoodID             int not null,
   primary key (fdCategoryID, fdFoodID)
);

alter table tbCategory_Food comment '��Ʒ��������Ķ�Ӧ��ϵ';

/*==============================================================*/
/* Table: tbChannel                                             */
/*==============================================================*/
create table tbChannel
(
   id                   int not null auto_increment,
   fdName               varchar(64),
   fdAbbreviate         varchar(16),
   primary key (id)
);

alter table tbChannel comment '������������������ʳ��������ʳ����������������ô�������ȵ�';

/*==============================================================*/
/* Table: tbDailyChannel                                        */
/*==============================================================*/
create table tbDailyChannel
(
   id                   int,
   fdRestaurantID       int,
   fdDate               date,
   fdChannelID          int,
   fdIncome             float(10,2) comment 'ʵ������',
   fdOrderCount         int comment '��������',
   fdServCount          numeric(8,1) comment '���ͷ���'
);

alter table tbDailyChannel comment '�����������������ձ�';

/*==============================================================*/
/* Table: tbDailyFood                                           */
/*==============================================================*/
create table tbDailyFood
(
   id                   int,
   fdDate               date,
   fdRestaurantID       int,
   fdFoodID             int,
   fdPlanCount          numeric(8,1) comment '�ƻ�����',
   fdServCount          numeric(8,1) comment '���ͷ���',
   fdIncome             float(10,2) comment 'ʵ������'
);

alter table tbDailyFood comment '����Ʒ�������ձ�';

/*==============================================================*/
/* Table: tbDailySummary                                        */
/*==============================================================*/
create table tbDailySummary
(
   id                   int,
   fdDate               date,
   fdRestaurantID       int,
   fdServCount          numeric(8,1) comment '���ͷ���',
   fdIncome             float(10,2) comment 'ʵ������'
);

alter table tbDailySummary comment '���ŵ������ձ�';

/*==============================================================*/
/* Table: tbDaily_Category                                      */
/*==============================================================*/
create table tbDaily_Category
(
   id                   int,
   fdRestaurantID       int,
   fdDate               date,
   fdCategoryID         int,
   fdServCount          numeric(8,1) comment '���ͷ���',
   fdIncome             float(10,2) comment 'ʵ������'
);

alter table tbDaily_Category comment '�������Ʒ�������ձ�';

/*==============================================================*/
/* Table: tbFood                                                */
/*==============================================================*/
create table tbFood
(
   id                   int not null auto_increment,
   fdUnitID             int,
   fdName               varchar(64),
   fdProduct            bool comment 'True-��Ʒ��False-ʳ��',
   fdOutputRate         float comment '������',
   primary key (id)
);

alter table tbFood comment '��Ʒ/ʳ��';

/*==============================================================*/
/* Table: tbFormula                                             */
/*==============================================================*/
create table tbFormula
(
   id                   int,
   fdProductFoodID      int,
   fdProductQuantity    numeric(8,2) comment '��Ʒ����Ʒ����',
   fdIngredientsFoodID  int comment 'ʳ�ı�ʶ',
   fdIngredientsQuantity numeric(8,2) comment 'ʳ������'
);

alter table tbFormula comment '�䷽��';

/*==============================================================*/
/* Table: tbOrder                                               */
/*==============================================================*/
create table tbOrder
(
   id                   int not null,
   tbS_id               int,
   fdDateTime           datetime,
   fdSystemID           int,
   fdAmount             float(8,2) comment 'ȫ�����',
   fdTakeAway           bool comment 'ȫ�����',
   fdRestaunantID       int,
   fdChannelID          int,
   primary key (id)
);

alter table tbOrder comment '����';

/*==============================================================*/
/* Table: tbOrder_Food                                          */
/*==============================================================*/
create table tbOrder_Food
(
   fdOrderID            int not null,
   fdFoodID             int not null,
   fdCount              int comment '����',
   fdAmount             float(8,2) comment '�ܼۡ����ڻ��С��ڶ�����ۡ���������˴�����¼���ۡ�',
   fdTakeAway           bool comment 'True-������',
   primary key (fdOrderID, fdFoodID)
);

alter table tbOrder_Food comment '������Ʒ��ϸ';

/*==============================================================*/
/* Table: tbRestaurant                                          */
/*==============================================================*/
create table tbRestaurant
(
   id                   int not null auto_increment,
   fdName               varchar(64),
   fdAbbreviate         varchar(16),
   primary key (id)
);

alter table tbRestaurant comment '�ŵ�';

/*==============================================================*/
/* Table: tbRestaurant_Food                                     */
/*==============================================================*/
create table tbRestaurant_Food
(
   fdRestaunantID       int not null,
   fdFoodID             int not null,
   fdShelves            bool comment 'True-�ϼܣ�Flase-�¼�',
   fdSoldOut            bool comment 'True-�ѹ���',
   primary key (fdRestaunantID, fdFoodID)
);

alter table tbRestaurant_Food comment '��Ʒ�ڸ��ŵ��ϼ�';

/*==============================================================*/
/* Table: tbSystem                                              */
/*==============================================================*/
create table tbSystem
(
   id                   int not null auto_increment,
   fdName               varchar(64),
   primary key (id)
);

alter table tbSystem comment '�ⲿϵͳ';

/*==============================================================*/
/* Table: tbUnit                                                */
/*==============================================================*/
create table tbUnit
(
   id                   int not null auto_increment,
   fdName               varchar(64),
   primary key (id)
);

alter table tbUnit comment '������λ����ˡ���';

alter table tbCategory_Food add constraint FK_Category_Food foreign key (fdCategoryID)
      references tbCategory (id) on delete restrict on update restrict;

alter table tbCategory_Food add constraint FK_Category_Food2 foreign key (fdFoodID)
      references tbFood (id) on delete restrict on update restrict;

alter table tbDailyChannel add constraint FK_Channel_DailyChannel foreign key (fdChannelID)
      references tbChannel (id) on delete restrict on update restrict;

alter table tbDailyChannel add constraint FK_Restaunant_DailyChannel foreign key (fdRestaurantID)
      references tbRestaurant (id) on delete restrict on update restrict;

alter table tbDailyFood add constraint FK_Food_DailyFood foreign key (fdFoodID)
      references tbFood (id) on delete restrict on update restrict;

alter table tbDailyFood add constraint FK_Restaunant_DailyFood foreign key (fdRestaurantID)
      references tbRestaurant (id) on delete restrict on update restrict;

alter table tbDailySummary add constraint FK_Restaunant_DailySummary foreign key (fdRestaurantID)
      references tbRestaurant (id) on delete restrict on update restrict;

alter table tbDaily_Category add constraint FK_Category_DailyCategory foreign key (fdCategoryID)
      references tbCategory (id) on delete restrict on update restrict;

alter table tbDaily_Category add constraint FK_Restaunant_DailyCategory foreign key (fdRestaurantID)
      references tbRestaurant (id) on delete restrict on update restrict;

alter table tbFood add constraint FK_Unit_Food foreign key (fdUnitID)
      references tbUnit (id) on delete restrict on update restrict;

alter table tbFormula add constraint FK_Food_Formula_Ingredients foreign key (fdIngredientsFoodID)
      references tbFood (id) on delete restrict on update restrict;

alter table tbFormula add constraint FK_Food_Formula_Product foreign key (fdProductFoodID)
      references tbFood (id) on delete restrict on update restrict;

alter table tbOrder add constraint FK_Channel_Order foreign key (fdChannelID)
      references tbChannel (id) on delete restrict on update restrict;

alter table tbOrder add constraint FK_Restaunant_Order foreign key (fdRestaunantID)
      references tbRestaurant (id) on delete restrict on update restrict;

alter table tbOrder add constraint FK_System_Order foreign key (fdSystemID)
      references tbSystem (id) on delete restrict on update restrict;

alter table tbOrder_Food add constraint FK_Order_Food foreign key (fdFoodID)
      references tbOrder (id) on delete restrict on update restrict;

alter table tbOrder_Food add constraint FK_Order_Food2 foreign key (fdFoodID)
      references tbFood (id) on delete restrict on update restrict;

alter table tbRestaurant_Food add constraint FK_Restaurant_Food foreign key (fdRestaunantID)
      references tbRestaurant (id) on delete restrict on update restrict;

alter table tbRestaurant_Food add constraint FK_Restaurant_Food2 foreign key (fdFoodID)
      references tbFood (id) on delete restrict on update restrict;

