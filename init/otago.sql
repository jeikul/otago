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

alter table tbCategory comment '产品分类，如沙拉、焗饭、意粉、盖饭、魔芋丝，等等';

/*==============================================================*/
/* Table: tbCategory_Food                                       */
/*==============================================================*/
create table tbCategory_Food
(
   fdCategoryID         int not null,
   fdFoodID             int not null,
   primary key (fdCategoryID, fdFoodID)
);

alter table tbCategory_Food comment '产品归属分类的对应关系';

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

alter table tbChannel comment '销售渠道，如美团堂食、美餐堂食、美团外卖、饿了么外卖，等等';

/*==============================================================*/
/* Table: tbDailyChannel                                        */
/*==============================================================*/
create table tbDailyChannel
(
   id                   int,
   fdRestaurantID       int,
   fdDate               date,
   fdChannelID          int,
   fdIncome             float(10,2) comment '实际收入',
   fdOrderCount         int comment '订单数量',
   fdServCount          numeric(8,1) comment '出餐份数'
);

alter table tbDailyChannel comment '各销售渠道的销量日报';

/*==============================================================*/
/* Table: tbDailyFood                                           */
/*==============================================================*/
create table tbDailyFood
(
   id                   int,
   fdDate               date,
   fdRestaurantID       int,
   fdFoodID             int,
   fdPlanCount          numeric(8,1) comment '计划数量',
   fdServCount          numeric(8,1) comment '出餐份数',
   fdIncome             float(10,2) comment '实际收入'
);

alter table tbDailyFood comment '各产品的销量日报';

/*==============================================================*/
/* Table: tbDailySummary                                        */
/*==============================================================*/
create table tbDailySummary
(
   id                   int,
   fdDate               date,
   fdRestaurantID       int,
   fdServCount          numeric(8,1) comment '出餐份数',
   fdIncome             float(10,2) comment '实际收入'
);

alter table tbDailySummary comment '各门店销量日报';

/*==============================================================*/
/* Table: tbDaily_Category                                      */
/*==============================================================*/
create table tbDaily_Category
(
   id                   int,
   fdRestaurantID       int,
   fdDate               date,
   fdCategoryID         int,
   fdServCount          numeric(8,1) comment '出餐份数',
   fdIncome             float(10,2) comment '实际收入'
);

alter table tbDaily_Category comment '各分类产品的销量日报';

/*==============================================================*/
/* Table: tbFood                                                */
/*==============================================================*/
create table tbFood
(
   id                   int not null auto_increment,
   fdUnitID             int,
   fdName               varchar(64),
   fdProduct            bool comment 'True-产品；False-食材',
   fdOutputRate         float comment '出料率',
   primary key (id)
);

alter table tbFood comment '产品/食材';

/*==============================================================*/
/* Table: tbFormula                                             */
/*==============================================================*/
create table tbFormula
(
   id                   int,
   fdProductFoodID      int,
   fdProductQuantity    numeric(8,2) comment '成品或半成品数量',
   fdIngredientsFoodID  int comment '食材标识',
   fdIngredientsQuantity numeric(8,2) comment '食材数量'
);

alter table tbFormula comment '配方表';

/*==============================================================*/
/* Table: tbOrder                                               */
/*==============================================================*/
create table tbOrder
(
   id                   int not null,
   tbS_id               int,
   fdDateTime           datetime,
   fdSystemID           int,
   fdAmount             float(8,2) comment '全单金额',
   fdTakeAway           bool comment '全单打包',
   fdRestaunantID       int,
   fdChannelID          int,
   primary key (id)
);

alter table tbOrder comment '订单';

/*==============================================================*/
/* Table: tbOrder_Food                                          */
/*==============================================================*/
create table tbOrder_Food
(
   fdOrderID            int not null,
   fdFoodID             int not null,
   fdCount              int comment '份数',
   fdAmount             float(8,2) comment '总价。由于会有“第二件半价”等情况，此处不记录单价。',
   fdTakeAway           bool comment 'True-单项打包',
   primary key (fdOrderID, fdFoodID)
);

alter table tbOrder_Food comment '订单商品明细';

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

alter table tbRestaurant comment '门店';

/*==============================================================*/
/* Table: tbRestaurant_Food                                     */
/*==============================================================*/
create table tbRestaurant_Food
(
   fdRestaunantID       int not null,
   fdFoodID             int not null,
   fdShelves            bool comment 'True-上架；Flase-下架',
   fdSoldOut            bool comment 'True-已沽清',
   primary key (fdRestaunantID, fdFoodID)
);

alter table tbRestaurant_Food comment '产品在各门店上架';

/*==============================================================*/
/* Table: tbSystem                                              */
/*==============================================================*/
create table tbSystem
(
   id                   int not null auto_increment,
   fdName               varchar(64),
   primary key (id)
);

alter table tbSystem comment '外部系统';

/*==============================================================*/
/* Table: tbUnit                                                */
/*==============================================================*/
create table tbUnit
(
   id                   int not null auto_increment,
   fdName               varchar(64),
   primary key (id)
);

alter table tbUnit comment '计量单位，如克、件';

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

