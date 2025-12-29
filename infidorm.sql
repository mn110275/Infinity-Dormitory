DROP DATABASE infidorm;
CREATE DATABASE infidorm;
USE infidorm;
CREATE TABLE USERS
(
    USER_ID INT AUTO_INCREMENT PRIMARY KEY,
    USER_ROLE INT,
    EMAIL VARCHAR(100) UNIQUE NOT NULL,
    PASS VARCHAR(255) NOT NULL,
    EMAIL_VERIFIED_AT DATETIME NULL,
    REMEMBER_TOKEN VARCHAR(255) NULL
);

CREATE TABLE BLOCK
(
    BLOCK_ID VARCHAR(10) PRIMARY KEY
);

CREATE TABLE MANAGER
(
    MNG_ID VARCHAR(20) PRIMARY KEY,
    MNG_NAME VARCHAR(100) NOT NULL,
    MNG_DOB DATETIME NOT NULL,
    MNG_GD VARCHAR(10),
    MNG_PHONE VARCHAR(20),
    MNG_ADR VARCHAR(200),
    MNG_IMG VARCHAR(255),
    MNG_BLOCK VARCHAR(10),
    USER_ID INT NOT NULL,
    FOREIGN KEY (USER_ID) REFERENCES USERS(USER_ID),
    FOREIGN KEY (MNG_BLOCK) REFERENCES BLOCK(BLOCK_ID)
);

CREATE TABLE ROOM
(
    ROOM_ID VARCHAR(10),
    GENDER VARCHAR(10),
    CAPACITY INT,
    OCCUPIED INT NOT NULL DEFAULT 0,
    BLOCK_ID VARCHAR(10),
    FOREIGN KEY (BLOCK_ID) REFERENCES BLOCK(BLOCK_ID),
    PRIMARY KEY (BLOCK_ID, ROOM_ID)
);

CREATE TABLE STUDENT
(
    STD_ID VARCHAR(20) NOT NULL PRIMARY KEY,
    STD_NAME VARCHAR(100) NOT NULL,
    STD_DOB DATETIME NOT NULL,
    STD_GD VARCHAR(10) NOT NULL,
    STD_PHONE VARCHAR(20),
    STD_ADR VARCHAR(200) NOT NULL,
    STARTDATE DATETIME,
    ENDDATE DATETIME,
    STD_IMG VARCHAR(255),
    USER_ID INT NOT NULL,
    BLOCK_ID VARCHAR(10),
    ROOM_ID VARCHAR(10),
    FOREIGN KEY (BLOCK_ID, ROOM_ID) REFERENCES ROOM(BLOCK_ID, ROOM_ID),
    FOREIGN KEY (USER_ID) REFERENCES USERS(USER_ID),
    FOREIGN KEY (BLOCK_ID) REFERENCES BLOCK(BLOCK_ID)
);

CREATE TABLE PROBLEM
(
    PR_ID INT AUTO_INCREMENT PRIMARY KEY,
    PR_TITLE VARCHAR(255) NOT NULL,
    CONTENT VARCHAR(255),
    STD_ID VARCHAR(20) NOT NULL,
    BLOCK_ID VARCHAR(20) NOT NULL,
    FOREIGN KEY (STD_ID) REFERENCES STUDENT(STD_ID),
    FOREIGN KEY (BLOCK_ID) REFERENCES BLOCK(BLOCK_ID)
);

-- Khoản thu(ID, Năm, Tháng, Tòa, Phòng, Số điện, Khối nước, Khác, Note đi kèm nếu có khác)
CREATE TABLE REVENUE
(
    REV_ID INT AUTO_INCREMENT PRIMARY KEY,
    REV_YEAR INT,
    REV_MONTH INT,
    BLOCK_ID VARCHAR(10) NOT NULL,
    ROOM_ID VARCHAR(10) NOT NULL,
    ELEC INT,
    WATER INT,
    OTHER INT,
    NOTE VARCHAR(255),
    FOREIGN KEY(BLOCK_ID) REFERENCES BLOCK(BLOCK_ID),
    FOREIGN KEY (BLOCK_ID, ROOM_ID) REFERENCES ROOM(BLOCK_ID, ROOM_ID)
);

-- Đơn Giá (Điện, Nước, theo từng tháng / năm cụ thể, có thể nhập tay tùy ý, do ADMIN làm, các tòa giống như nhau)
CREATE TABLE UNIT
(
    UYEAR INT,
    UMONTH INT,
    ELEC INT NOT NULL,
    WATER INT NOT NULL,
    PRIMARY KEY (UYEAR, UMONTH)
);

CREATE TABLE FACILITY
(
    FCLT_ID INT AUTO_INCREMENT PRIMARY KEY,
    FCLT_TYPE VARCHAR(100) NOT NULL,
    FCLT_STATUS VARCHAR(50),
    FCLT_NOTE VARCHAR(255),
    FCLT_IMG VARCHAR(255),
    ROOM_ID VARCHAR(10) NOT NULL,
    BLOCK_ID VARCHAR(10) NOT NULL,
    FOREIGN KEY(BLOCK_ID) REFERENCES ROOM(BLOCK_ID),
    FOREIGN KEY (BLOCK_ID, ROOM_ID) REFERENCES ROOM(BLOCK_ID, ROOM_ID)
);

CREATE TABLE REGIFORM
(
    REG_ID INT AUTO_INCREMENT PRIMARY KEY,
    REG_NAME VARCHAR(100) NOT NULL,
    REG_STD_ID VARCHAR(20) NOT NULL,
    REG_PHONE VARCHAR(20) NOT NULL,
    REG_EMAIL VARCHAR(100) NOT NULL,
    REG_STATUS VARCHAR(50)
);

CREATE TABLE NOTI
(
    NOTI_ID INT AUTO_INCREMENT PRIMARY KEY,
    TITLE VARCHAR(255) NOT NULL,
    CONTENT VARCHAR(255),
    NOTI_DATE DATETIME,
    MNG_ID VARCHAR(20),
    FOREIGN KEY (MNG_ID) REFERENCES MANAGER(MNG_ID)
);

DELIMITER $$    -- Trigger: Khi thêm SV thì tăng OCCUPIED lên 1
CREATE TRIGGER trg_student_insert
AFTER INSERT ON STUDENT
FOR EACH ROW
BEGIN
    UPDATE ROOM
    SET OCCUPIED = OCCUPIED + 1
    WHERE ROOM_ID = NEW.ROOM_ID AND BLOCK_ID = NEW.BLOCK_ID;
END$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER trg_student_delete
AFTER DELETE ON STUDENT
FOR EACH ROW
BEGIN
    UPDATE ROOM
    SET OCCUPIED = OCCUPIED - 1
    WHERE ROOM_ID = OLD.ROOM_ID AND BLOCK_ID = OLD.BLOCK_ID;
END$$
DELIMITER ;

DELIMITER $$    -- Trigger: Khi đổi phòng cho SV thì phòng cũ giảm OCCUPIED đi 1, phòng mới tăng 1
CREATE TRIGGER trg_student_update_room
AFTER UPDATE ON STUDENT
FOR EACH ROW
BEGIN
    IF OLD.ROOM_ID <> NEW.ROOM_ID THEN
        UPDATE ROOM
        SET OCCUPIED = OCCUPIED - 1
        WHERE ROOM_ID = OLD.ROOM_ID AND BLOCK_ID = OLD.BLOCK_ID;

        UPDATE ROOM
        SET OCCUPIED = OCCUPIED + 1
        WHERE ROOM_ID = NEW.ROOM_ID AND BLOCK_ID = NEW.BLOCK_ID;
    END IF;
END$$
DELIMITER ;

DELIMITER $$    -- Trigger: Không thể thêm SV vào phòng đã đầy
CREATE TRIGGER trg_check_capacity
BEFORE INSERT ON STUDENT
FOR EACH ROW
BEGIN
    DECLARE cur INT;
    DECLARE cap INT;

    SELECT OCCUPIED, CAPACITY
    INTO cur, cap
    FROM ROOM
    WHERE ROOM_ID = NEW.ROOM_ID AND BLOCK_ID = NEW.BLOCK_ID;

    IF cur >= cap THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Phòng đã đủ người';
    END IF;
END$$
DELIMITER ;


INSERT INTO USERS (EMAIL, PASS, EMAIL_VERIFIED_AT)
VALUES
('admin@infidorm.com', '123456', NOW()),
('manager@infidorm.com', '123456', NOW()),
('mng@infidorm.com', '123456', NOW()),
('s1@infidorm.com', '123456', NOW()),
('s2@infidorm.com', '123456', NOW()),
('s3@infidorm.com', '123456', NOW()),
('s4@infidorm.com', '123456', NOW()),
('s5@infidorm.com', '123456', NOW()),
('s6@infidorm.com', '123456', NOW()),
('s7@infidorm.com', '123456', NOW()),
('s8@infidorm.com', '123456', NOW());

INSERT INTO BLOCK (BLOCK_ID)
VALUES ('A'), ('B'), ('C'), ('D');

INSERT INTO MANAGER
(MNG_ID, MNG_NAME, MNG_DOB, MNG_GD, MNG_PHONE, MNG_ADR, USER_ID, MNG_BLOCK)
VALUES
('M01', 'Nguyễn Văn A', '1985-05-20', 'Nam', '0901234567', 'Hà Nội', 2, 'A'),
('M02', 'Nguyễn Thị B', '1986-06-21', 'Nữ', '0901234568', 'Hà Nội', 3, 'A');

INSERT INTO ROOM (ROOM_ID, GENDER, CAPACITY, BLOCK_ID)
VALUES
('101', 'Nam', 6, 'A'),
('102', 'Nam', 8, 'A'),
('201', 'Nữ', 8, 'A'),
('202', 'Nữ', 6, 'A'),
('101', 'Nam', 6, 'B'),
('102', 'Nam', 8, 'B'),
('201', 'Nữ', 6, 'B'),
('202', 'Nữ', 8, 'B');

INSERT INTO STUDENT
(STD_ID, STD_NAME, STD_DOB, STD_GD, STD_PHONE, STD_ADR, USER_ID, BLOCK_ID, ROOM_ID)
VALUES
('20230002', 'Lê Văn C', '2003-08-10', 'Nam', '0977123456', 'Thái Bình', 4, 'A', '101'),
('20210001', 'Phạm Minh Tuấn', '2003-05-10', 'Nam', '0987654321', 'Hà Nội', 5, 'A', '101'),
('20210005', 'Trần Văn Đức', '2003-06-30', 'Nam', '0987654325', 'Nghệ An', 9, 'A', '102'),
('20210006', 'Trần Văn Đức', '2003-06-30', 'Nam', '0987654325', 'Nghệ An', 10, 'A', '102'),
('20230001', 'Trần Thị B', '2004-03-15', 'Nữ', '0987654321', 'Nam Định', 3, 'A', '201'),
('20210002', 'Lê Thị Hoa', '2003-08-15', 'Nữ', '0987654322', 'Hải Phòng', 6, 'B', '201'),
('20210003', 'Hoàng Văn Nam', '2003-02-20', 'Nam', '0987654323', 'Nam Định', 7, 'B', '102'),
('20210004', 'Nguyễn Thị Lan', '2003-11-05', 'Nữ', '0987654324', 'Thanh Hóa', 8, 'B', '201');


INSERT INTO REGIFORM (REG_NAME, REG_STD_ID, REG_PHONE, REG_EMAIL)
VALUES
('Vũ Thị Mai', '20220001', '0912345678', 'mai@student.hust.edu.vn'),
('Đỗ Văn Hùng', '20220002', '0912345679', 'hung@student.hust.edu.vn'),
('Bùi Thị Thu', '20220003', '0912345680', 'thu@student.hust.edu.vn');

-- PHÒNG 101 (6 người)
INSERT INTO FACILITY (FCLT_TYPE, FCLT_STATUS, FCLT_NOTE, FCLT_IMG, ROOM_ID, BLOCK_ID) VALUES
-- Giường tầng
('Giường tầng', 'Tốt', 'Giường tầng số 1', 'bed1.jpg', '101', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 2', 'bed2.jpg', '101', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 3', 'bed3.jpg', '101', 'A'),
-- Tủ quần áo
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 1', 'wardrobe1.jpg', '101', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 2', 'wardrobe2.jpg', '101', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 3', 'wardrobe3.jpg', '101', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 4', 'wardrobe4.jpg', '101', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 5', 'wardrobe5.jpg', '101', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 6', 'wardrobe6.jpg', '101', 'A'),
-- Bàn học
('Bàn học', 'Tốt', 'Bàn học chung 6 chỗ', 'desk_long.jpg', '101', 'A'),
('Ghế ngồi', 'Tốt', 'Bộ 6 ghế học', 'chair_set.jpg', '101', 'A'),
-- Điều hòa & Quạt
('Điều hòa', 'Tốt', '18000 BTU', 'ac.jpg', '101', 'A'),
('Quạt trần', 'Tốt', 'Quạt trần 5 cánh', 'ceiling_fan.jpg', '101', 'A'),
-- Đèn
('Đèn tuýp LED', 'Tốt', '40W - chiếu sáng chính', 'led_main.jpg', '101', 'A'),
('Đèn bàn học', 'Tốt', 'Đèn LED bàn học', 'desk_lamp.jpg', '101', 'A'),
-- Kệ & Giá treo
('Kệ sách', 'Tốt', 'Kệ gỗ 4 tầng', 'bookshelf.jpg', '101', 'A'),
('Giá treo quần áo', 'Tốt', 'Inox 10 móc', 'hanger.jpg', '101', 'A'),
-- Thùng rác & Dọn dẹp
('Thùng rác', 'Tốt', 'Thùng 20 lít', 'bin.jpg', '101', 'A'),
('Chổi & Hốt rác', 'Tốt', 'Bộ dọn vệ sinh', 'broom.jpg', '101', 'A'),
-- Tiện ích khác
('Gương soi', 'Tốt', 'Gương treo tường 60x80cm', 'mirror.jpg', '101', 'A'),
('Móc khóa cửa', 'Tốt', 'Ổ khóa an toàn', 'lock.jpg', '101', 'A');

-- PHÒNG 102 (6 người)
INSERT INTO FACILITY (FCLT_TYPE, FCLT_STATUS, FCLT_NOTE, FCLT_IMG, ROOM_ID, BLOCK_ID) VALUES
('Giường tầng', 'Tốt', 'Giường tầng số 1', 'bed1.jpg', '102', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 2', 'bed2.jpg', '102', 'A'),
('Giường tầng', 'Cần sửa', 'Giường tầng số 3 - Bậc thang lỏng', 'bed3.jpg', '102', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 1', 'wardrobe1.jpg', '102', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 2', 'wardrobe2.jpg', '102', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 3', 'wardrobe3.jpg', '102', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 4', 'wardrobe4.jpg', '102', 'A'),
('Tủ quần áo', 'Hỏng', 'Tủ sinh viên 5 - Cửa bị rớt', 'wardrobe5.jpg', '102', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ cá nhân sinh viên 6', 'wardrobe6.jpg', '102', 'A'),
('Bàn học', 'Tốt', 'Bàn học chung 6 chỗ', 'desk_long.jpg', '102', 'A'),
('Ghế ngồi', 'Tốt', 'Bộ 6 ghế học', 'chair_set.jpg', '102', 'A'),
('Điều hòa', 'Hỏng', '18000 BTU - Không làm lạnh', 'ac_broken.jpg', '102', 'A'),
('Quạt trần', 'Tốt', 'Quạt trần 5 cánh', 'ceiling_fan.jpg', '102', 'A'),
('Quạt đứng', 'Tốt', 'Quạt đứng dự phòng', 'stand_fan.jpg', '102', 'A'),
('Đèn tuýp LED', 'Tốt', '40W - chiếu sáng chính', 'led_main.jpg', '102', 'A'),
('Đèn bàn học', 'Tốt', 'Đèn LED bàn học', 'desk_lamp.jpg', '102', 'A'),
('Kệ sách', 'Tốt', 'Kệ gỗ 4 tầng', 'bookshelf.jpg', '102', 'A'),
('Giá treo quần áo', 'Tốt', 'Inox 10 móc', 'hanger.jpg', '102', 'A'),
('Thùng rác', 'Tốt', 'Thùng 20 lít', 'bin.jpg', '102', 'A'),
('Chổi & Hốt rác', 'Tốt', 'Bộ dọn vệ sinh', 'broom.jpg', '102', 'A'),
('Gương soi', 'Tốt', 'Gương treo tường 60x80cm', 'mirror.jpg', '102', 'A'),
('Móc khóa cửa', 'Tốt', 'Ổ khóa an toàn', 'lock.jpg', '102', 'A'),
('Rèm cửa', 'Cần sửa', 'Rèm vải - Bị rách góc', 'curtain.jpg', '102', 'A');

-- PHÒNG 201 (8 người)
INSERT INTO FACILITY (FCLT_TYPE, FCLT_STATUS, FCLT_NOTE, FCLT_IMG, ROOM_ID, BLOCK_ID) VALUES
('Giường tầng', 'Tốt', 'Giường tầng số 1', 'bed1.jpg', '201', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 2', 'bed2.jpg', '201', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 3', 'bed3.jpg', '201', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 4', 'bed4.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 1', 'wardrobe1.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 2', 'wardrobe2.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 3', 'wardrobe3.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 4', 'wardrobe4.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 5', 'wardrobe5.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 6', 'wardrobe6.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 7', 'wardrobe7.jpg', '201', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 8', 'wardrobe8.jpg', '201', 'A'),
('Bàn học', 'Tốt', 'Bàn học lớn 8 chỗ', 'desk_large.jpg', '201', 'A'),
('Ghế ngồi', 'Tốt', 'Bộ 8 ghế học', 'chair_set8.jpg', '201', 'A'),
('Điều hòa', 'Tốt', '24000 BTU - Công suất lớn', 'ac_large.jpg', '201', 'A'),
('Quạt trần', 'Tốt', 'Quạt trần 6 cánh công nghiệp', 'ceiling_fan_large.jpg', '201', 'A'),
('Quạt đứng', 'Tốt', 'Quạt đứng số 1', 'stand_fan1.jpg', '201', 'A'),
('Quạt đứng', 'Tốt', 'Quạt đứng số 2', 'stand_fan2.jpg', '201', 'A'),
('Đèn tuýp LED', 'Tốt', '60W - Chiếu sáng chính', 'led_main60.jpg', '201', 'A'),
('Đèn tuýp LED', 'Tốt', '40W - Chiếu sáng phụ', 'led_sub40.jpg', '201', 'A'),
('Đèn bàn học', 'Tốt', 'Đèn LED bàn học số 1', 'desk_lamp1.jpg', '201', 'A'),
('Đèn bàn học', 'Tốt', 'Đèn LED bàn học số 2', 'desk_lamp2.jpg', '201', 'A'),
('Kệ sách', 'Tốt', 'Kệ gỗ lớn 5 tầng', 'bookshelf_large.jpg', '201', 'A'),
('Kệ đựng đồ', 'Tốt', 'Kệ nhựa đa năng', 'storage_shelf.jpg', '201', 'A'),
('Giá treo quần áo', 'Tốt', 'Inox 15 móc', 'hanger_large.jpg', '201', 'A'),
('Thùng rác', 'Tốt', 'Thùng 30 lít', 'bin_large.jpg', '201', 'A'),
('Chổi & Hốt rác', 'Tốt', 'Bộ dọn vệ sinh', 'broom.jpg', '201', 'A'),
('Lau nhà', 'Tốt', 'Bộ lau nhà xô + cây', 'mop.jpg', '201', 'A'),
('Gương soi', 'Tốt', 'Gương treo tường 80x100cm', 'mirror_large.jpg', '201', 'A'),
('Móc khóa cửa', 'Tốt', 'Ổ khóa an toàn', 'lock.jpg', '201', 'A'),
('Rèm cửa', 'Tốt', 'Rèm vải cao cấp', 'curtain_good.jpg', '201', 'A'),
('Bình nước', 'Tốt', 'Bình nước 20 lít', 'water_jug.jpg', '201', 'A'),
('Giỏ đựng đồ', 'Tốt', 'Giỏ nhựa đa năng', 'basket.jpg', '201', 'A');

-- PHÒNG 202 (8 người)
INSERT INTO FACILITY (FCLT_TYPE, FCLT_STATUS, FCLT_NOTE, FCLT_IMG, ROOM_ID, BLOCK_ID) VALUES
('Giường tầng', 'Tốt', 'Giường tầng số 1', 'bed1.jpg', '202', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 2', 'bed2.jpg', '202', 'A'),
('Giường tầng', 'Tốt', 'Giường tầng số 3', 'bed3.jpg', '202', 'A'),
('Giường tầng', 'Cần thay', 'Giường tầng số 4 - Nệm cũ', 'bed4_old.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 1', 'wardrobe1.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 2', 'wardrobe2.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 3', 'wardrobe3.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 4', 'wardrobe4.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 5', 'wardrobe5.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 6', 'wardrobe6.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 7', 'wardrobe7.jpg', '202', 'A'),
('Tủ quần áo', 'Tốt', 'Tủ sinh viên 8', 'wardrobe8.jpg', '202', 'A'),
('Bàn học', 'Tốt', 'Bàn học lớn 8 chỗ', 'desk_large.jpg', '202', 'A'),
('Ghế ngồi', 'Cần sửa', 'Bộ 8 ghế - 2 ghế lỏng ốc', 'chair_set8.jpg', '202', 'A'),
('Điều hòa', 'Tốt', '24000 BTU', 'ac_large.jpg', '202', 'A'),
('Quạt trần', 'Tốt', 'Quạt trần 6 cánh', 'ceiling_fan_large.jpg', '202', 'A'),
('Quạt đứng', 'Hỏng', 'Quạt đứng - Motor cháy', 'stand_fan_broken.jpg', '202', 'A'),
('Đèn tuýp LED', 'Tốt', '60W chính', 'led_main60.jpg', '202', 'A'),
('Đèn tuýp LED', 'Hỏng', '40W phụ - Bóng chết', 'led_broken.jpg', '202', 'A'),
('Đèn bàn học', 'Tốt', 'Đèn LED bàn học', 'desk_lamp.jpg', '202', 'A'),
('Kệ sách', 'Tốt', 'Kệ gỗ 5 tầng', 'bookshelf_large.jpg', '202', 'A'),
('Giá treo quần áo', 'Tốt', 'Inox 15 móc', 'hanger_large.jpg', '202', 'A'),
('Thùng rác', 'Tốt', 'Thùng 30 lít', 'bin_large.jpg', '202', 'A'),
('Chổi & Hốt rác', 'Tốt', 'Bộ dọn vệ sinh', 'broom.jpg', '202', 'A'),
('Gương soi', 'Tốt', 'Gương 80x100cm', 'mirror_large.jpg', '202', 'A'),
('Móc khóa cửa', 'Cần thay', 'Ổ khóa cũ - Khóa khó', 'lock_old.jpg', '202', 'A'),
('Rèm cửa', 'Tốt', 'Rèm vải', 'curtain.jpg', '202', 'A'),
('Bình nước', 'Tốt', 'Bình 20 lít', 'water_jug.jpg', '202', 'A');

-- PHÒNG 101 (8 người)
INSERT INTO FACILITY (FCLT_TYPE, FCLT_STATUS, FCLT_NOTE, FCLT_IMG, ROOM_ID, BLOCK_ID) VALUES
('Giường tầng', 'Tốt', 'Giường số 1', 'bed1.jpg', '101', 'B'),
('Giường tầng', 'Tốt', 'Giường số 2', 'bed2.jpg', '101', 'B'),
('Giường tầng', 'Tốt', 'Giường số 3', 'bed3.jpg', '101', 'B'),
('Giường tầng', 'Tốt', 'Giường số 4', 'bed4.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 1', 'wardrobe1.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 2', 'wardrobe2.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 3', 'wardrobe3.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 4', 'wardrobe4.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 5', 'wardrobe5.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 6', 'wardrobe6.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 7', 'wardrobe7.jpg', '101', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 8', 'wardrobe8.jpg', '101', 'B'),
('Bàn học', 'Tốt', 'Bàn 8 chỗ', 'desk_large.jpg', '101', 'B'),
('Ghế ngồi', 'Tốt', '8 ghế', 'chair_set8.jpg', '101', 'B'),
('Điều hòa', 'Tốt', '24000 BTU', 'ac_large.jpg', '101', 'B'),
('Quạt trần', 'Tốt', 'Quạt 6 cánh', 'ceiling_fan_large.jpg', '101', 'B'),
('Đèn tuýp LED', 'Tốt', '60W', 'led_main60.jpg', '101', 'B'),
('Đèn tuýp LED', 'Tốt', '40W', 'led_sub40.jpg', '101', 'B'),
('Đèn bàn học', 'Tốt', 'Đèn LED', 'desk_lamp.jpg', '101', 'B'),
('Kệ sách', 'Tốt', 'Kệ 5 tầng', 'bookshelf_large.jpg', '101', 'B'),
('Giá treo', 'Tốt', '15 móc', 'hanger_large.jpg', '101', 'B'),
('Thùng rác', 'Tốt', '30L', 'bin_large.jpg', '101', 'B'),
('Chổi', 'Tốt', 'Bộ dọn', 'broom.jpg', '101', 'B'),
('Gương', 'Tốt', '80x100cm', 'mirror_large.jpg', '101', 'B'),
('Khóa cửa', 'Tốt', 'Khóa an toàn', 'lock.jpg', '101', 'B'),
('Rèm', 'Tốt', 'Rèm vải', 'curtain.jpg', '101', 'B');

-- PHÒNG 102 (8 người)
INSERT INTO FACILITY (FCLT_TYPE, FCLT_STATUS, FCLT_NOTE, FCLT_IMG, ROOM_ID, BLOCK_ID) VALUES
('Giường tầng', 'Tốt', 'Giường 1', 'bed1.jpg', '102', 'B'),
('Giường tầng', 'Tốt', 'Giường 2', 'bed2.jpg', '102', 'B'),
('Giường tầng', 'Tốt', 'Giường 3', 'bed3.jpg', '102', 'B'),
('Giường tầng', 'Tốt', 'Giường 4', 'bed4.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 1', 'wardrobe1.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 2', 'wardrobe2.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 3', 'wardrobe3.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 4', 'wardrobe4.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 5', 'wardrobe5.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 6', 'wardrobe6.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 7', 'wardrobe7.jpg', '102', 'B'),
('Tủ quần áo', 'Tốt', 'Tủ 8', 'wardrobe8.jpg', '102', 'B'),
('Bàn học', 'Tốt', 'Bàn 8 chỗ', 'desk_large.jpg', '102', 'B'),
('Ghế ngồi', 'Tốt', '8 ghế', 'chair_set8.jpg', '102', 'B'),
('Điều hòa', 'Tốt', '24000 BTU', 'ac_large.jpg', '102', 'B'),
('Quạt trần', 'Tốt', 'Quạt 6 cánh', 'ceiling_fan_large.jpg', '102', 'B'),
('Quạt đứng', 'Tốt', 'Quạt phụ', 'stand_fan.jpg', '102', 'B'),
('Đèn tuýp LED', 'Tốt', '60W chính', 'led_main60.jpg', '102', 'B'),
('Đèn tuýp LED', 'Tốt', '40W phụ', 'led_sub40.jpg', '102', 'B'),
('Đèn bàn học', 'Tốt', 'Đèn LED 1', 'desk_lamp1.jpg', '102', 'B'),
('Đèn bàn học', 'Tốt', 'Đèn LED 2', 'desk_lamp2.jpg', '102', 'B'),
('Kệ sách', 'Tốt', 'Kệ 5 tầng', 'bookshelf_large.jpg', '102', 'B'),
('Kệ đồ', 'Tốt', 'Kệ nhựa', 'storage_shelf.jpg', '102', 'B'),
('Giá treo', 'Tốt', '15 móc', 'hanger_large.jpg', '102', 'B'),
('Thùng rác', 'Tốt', '30L', 'bin_large.jpg', '102', 'B'),
('Chổi', 'Tốt', 'Bộ dọn', 'broom.jpg', '102', 'B'),
('Lau nhà', 'Tốt', 'Bộ lau', 'mop.jpg', '102', 'B'),
('Gương', 'Tốt', '80x100cm', 'mirror_large.jpg', '102', 'B'),
('Khóa cửa', 'Tốt', 'Khóa mới', 'lock.jpg', '102', 'B'),
('Rèm', 'Tốt', 'Rèm cao cấp', 'curtain_good.jpg', '102', 'B'),
('Bình nước', 'Tốt', '20L', 'water_jug.jpg', '102', 'B'),
('Giỏ đựng', 'Tốt', 'Giỏ nhựa', 'basket.jpg', '102', 'B'),
('Móc dán tường', 'Tốt', '10 móc 3M', 'wall_hook.jpg', '102', 'B');

/* ------------------------
Code sinh dữ liệu ngẫu nhiên cho:
1. Đơn giá điện, nước tháng 12/2025 (bảng UNIT)
2. Khoản thu cho TẤT CẢ các phòng hiện có trong tháng 12/2025 (bảng REVENUE)
-------------------------- */

-- 1. Điền đơn giá cho điện, nước trong bảng UNIT (Tháng 12/2025)
INSERT INTO UNIT (UYEAR, UMONTH, ELEC, WATER)
VALUES (2025, 12, 3500, 15000); 
-- Giả định: 3,500đ/số điện và 15,000đ/khối nước

-- 2. Điền các khoản thu cho TẤT CẢ các phòng hiện có trong tháng 12/2025
-- Sử dụng INSERT INTO ... SELECT để tự động lấy danh sách phòng từ bảng ROOM
INSERT INTO REVENUE (REV_YEAR, REV_MONTH, BLOCK_ID, ROOM_ID, ELEC, WATER, OTHER, NOTE)
SELECT 
    2025, 
    12, 
    BLOCK_ID, 
    ROOM_ID, 
    FLOOR(RAND() * 200 + 50), -- Số điện ngẫu nhiên từ 50 - 250 số
    FLOOR(RAND() * 20 + 5),   -- Số nước ngẫu nhiên từ 5 - 25 khối
    50000,                    -- Chi phí khác mặc định (ví dụ vệ sinh/wifi)
    'Tiền điện nước tháng 12'
FROM ROOM;

-- 3. Thông báo nghỉ Tết dương lịch năm 2026
-- Giả định MNG_ID 'M01' là người đăng thông báo này
INSERT INTO NOTI (TITLE, CONTENT, NOTI_DATE, MNG_ID)
VALUES (
    'Thông báo nghỉ Tết Dương lịch 2026', 
    'Theo quy định của Nhà trường, sinh viên nội trú được nghỉ Tết Dương lịch 04 ngày, từ ngày 01/01/2026 đến hết ngày 04/01/2026. Yêu cầu sinh viên trước khi về nghỉ phải ngắt toàn bộ thiết bị điện và khóa vòi nước.', 
    '2025-12-25 08:00:00', 
    'M01'
);