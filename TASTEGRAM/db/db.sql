-- CREATE DATABASE IF NOT EXISTS dbapp;
-- USE dbapp;

-- -- 1. UTENTI
-- CREATE TABLE users (
--     id               INT AUTO_INCREMENT PRIMARY KEY,
--     username         VARCHAR(50)  NOT NULL UNIQUE,
--     email            VARCHAR(100) NOT NULL UNIQUE,
--     password         VARCHAR(255) NOT NULL,
--     bio              TEXT,
--     avatar_url       VARCHAR(255) DEFAULT 'default_avatar.png',
--     followers_count  INT UNSIGNED DEFAULT 0,
--     following_count  INT UNSIGNED DEFAULT 0,
--     created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     INDEX idx_username (username)
-- ) ENGINE=InnoDB;

-- -- 2. POST / RECENSIONI
-- CREATE TABLE posts (
--     id             INT AUTO_INCREMENT PRIMARY KEY,
--     user_id        INT          NOT NULL,
--     title_work     VARCHAR(255) NOT NULL,
--     content        TEXT         NOT NULL,
--     rating         TINYINT      NOT NULL CHECK (rating BETWEEN 1 AND 5),
--     cuisine_type   VARCHAR(100),
--     image_path     VARCHAR(255),
--     likes_count    INT UNSIGNED DEFAULT 0,
--     comments_count INT UNSIGNED DEFAULT 0,
--     created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_post_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

--     INDEX idx_user_created  (user_id, created_at DESC),
--     INDEX idx_created_at    (created_at DESC),
--     FULLTEXT INDEX ft_title_work (title_work)
-- ) ENGINE=InnoDB;

-- -- 3. FOLLOW
-- CREATE TABLE follows (
--     follower_id INT NOT NULL,
--     followed_id INT NOT NULL,
--     created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     PRIMARY KEY (follower_id, followed_id),
--     CONSTRAINT fk_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
--     CONSTRAINT fk_followed FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,

--     INDEX idx_followed_id (followed_id)
-- ) ENGINE=InnoDB;

-- -- 4. LIKE
-- CREATE TABLE likes (
--     post_id    INT NOT NULL,
--     user_id    INT NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     PRIMARY KEY (post_id, user_id),
--     CONSTRAINT fk_like_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
--     CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,

--     INDEX idx_like_user (user_id)
-- ) ENGINE=InnoDB;

-- -- 5. COMMENTI (con annidamento)
-- CREATE TABLE comments (
--     id         INT AUTO_INCREMENT PRIMARY KEY,
--     post_id    INT     NOT NULL,
--     user_id    INT     NOT NULL,
--     parent_id  INT     DEFAULT NULL,
--     depth      TINYINT DEFAULT 0,
--     content    TEXT    NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_comment_post   FOREIGN KEY (post_id)   REFERENCES posts(id)    ON DELETE CASCADE,
--     CONSTRAINT fk_comment_user   FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
--     CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE SET NULL,

--     INDEX idx_post_parent  (post_id, parent_id),
--     INDEX idx_post_created (post_id, created_at)
-- ) ENGINE=InnoDB;

-- -- 6. NOTIFICHE
-- CREATE TABLE notifications (
--     id         INT AUTO_INCREMENT PRIMARY KEY,
--     user_id    INT     NOT NULL,
--     actor_id   INT     NOT NULL,
--     post_id    INT     DEFAULT NULL,
--     type       ENUM('follow','like','comment') NOT NULL,
--     is_read    BOOLEAN DEFAULT FALSE,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

--     CONSTRAINT fk_notif_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
--     CONSTRAINT fk_notif_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
--     CONSTRAINT fk_notif_post  FOREIGN KEY (post_id)  REFERENCES posts(id) ON DELETE CASCADE,

--     INDEX idx_user_read    (user_id, is_read),
--     INDEX idx_user_created (user_id, created_at DESC)
-- ) ENGINE=InnoDB;

-- INSERT INTO users (username, email, password, bio, avatar_url) 
-- VALUES (
--     'ospite', 
--     'ospite@Tastegram.it', 
--     '1234', 
--     'Account predefinito per esplorare la piattaforma.', 
--     'default_avatar.png'
-- )
-- ON DUPLICATE KEY UPDATE username=username;

-- -- ─────────────────────────────────────────────
-- -- TRIGGER
-- -- ─────────────────────────────────────────────

-- DELIMITER $$

-- -- Like aggiunto
-- CREATE TRIGGER trg_like_insert
-- AFTER INSERT ON likes FOR EACH ROW
-- BEGIN
--     UPDATE posts SET likes_count = likes_count + 1 WHERE id = NEW.post_id;
-- END$$

-- -- Like rimosso
-- CREATE TRIGGER trg_like_delete
-- AFTER DELETE ON likes FOR EACH ROW
-- BEGIN
--     UPDATE posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = OLD.post_id;
-- END$$

-- -- Commento aggiunto
-- CREATE TRIGGER trg_comment_insert
-- AFTER INSERT ON comments FOR EACH ROW
-- BEGIN
--     UPDATE posts SET comments_count = comments_count + 1 WHERE id = NEW.post_id;
-- END$$

-- -- Commento eliminato
-- CREATE TRIGGER trg_comment_delete
-- AFTER DELETE ON comments FOR EACH ROW
-- BEGIN
--     UPDATE posts SET comments_count = GREATEST(comments_count - 1, 0) WHERE id = OLD.post_id;
-- END$$

-- -- Follow aggiunto
-- CREATE TRIGGER trg_follow_insert
-- AFTER INSERT ON follows FOR EACH ROW
-- BEGIN
--     UPDATE users SET followers_count = followers_count + 1 WHERE id = NEW.followed_id;
--     UPDATE users SET following_count = following_count + 1 WHERE id = NEW.follower_id;
-- END$$

-- -- Follow rimosso
-- CREATE TRIGGER trg_follow_delete
-- AFTER DELETE ON follows FOR EACH ROW
-- BEGIN
--     UPDATE users SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = OLD.followed_id;
--     UPDATE users SET following_count = GREATEST(following_count - 1, 0) WHERE id = OLD.follower_id;
-- END$$

-- DELIMITER ;

-- 1. Reset totale (ATTENZIONE: cancella i dati esistenti)
DROP DATABASE IF EXISTS dbapp;
CREATE DATABASE dbapp;
USE dbapp;

-- 2. TABELLA UTENTI
CREATE TABLE users (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    username         VARCHAR(50)  NOT NULL UNIQUE,
    email            VARCHAR(100) NOT NULL UNIQUE,
    password         VARCHAR(255) NOT NULL,
    bio              TEXT,
    avatar_url       VARCHAR(255) DEFAULT 'default_avatar.png',
    followers_count  INT UNSIGNED DEFAULT 0,
    following_count  INT UNSIGNED DEFAULT 0,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- 3. TABELLA POST (Con le colonne likes_count e comments_count)
CREATE TABLE posts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL,
    title_work     VARCHAR(255) NOT NULL,
    content        TEXT         NOT NULL,
    rating         TINYINT      NOT NULL CHECK (rating BETWEEN 1 AND 5),
    cuisine_type   VARCHAR(100),
    image_path     VARCHAR(255),
    likes_count    INT UNSIGNED DEFAULT 0,
    comments_count INT UNSIGNED DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_post_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created  (user_id, created_at DESC),
    INDEX idx_created_at    (created_at DESC),
    FULLTEXT INDEX ft_title_work (title_work)
) ENGINE=InnoDB;

-- 4. TABELLA FOLLOW
CREATE TABLE follows (
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (follower_id, followed_id),
    CONSTRAINT fk_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_followed FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. TABELLA LIKE
CREATE TABLE likes (
    post_id    INT NOT NULL,
    user_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (post_id, user_id),
    CONSTRAINT fk_like_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. TABELLA COMMENTI
CREATE TABLE comments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT     NOT NULL,
    user_id    INT     NOT NULL,
    parent_id  INT     DEFAULT NULL,
    content    TEXT    NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comment_post   FOREIGN KEY (post_id)   REFERENCES posts(id)    ON DELETE CASCADE,
    CONSTRAINT fk_comment_user   FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. TABELLA NOTIFICHE
CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT     NOT NULL,
    actor_id   INT     NOT NULL,
    post_id    INT     DEFAULT NULL,
    type       ENUM('follow','like','comment') NOT NULL,
    is_read    BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notif_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. INSERIMENTO UTENTE OSPITE
INSERT INTO users (username, email, password, bio) 
VALUES ('ospite', 'ospite@tastegram.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' , 'Account per visitatori');

-- 9. TRIGGER PER AGGIORNARE I CONTATORI AUTOMATICAMENTE
DELIMITER $$

CREATE TRIGGER trg_like_insert AFTER INSERT ON likes FOR EACH ROW
BEGIN
    UPDATE posts SET likes_count = likes_count + 1 WHERE id = NEW.post_id;
END$$

CREATE TRIGGER trg_like_delete AFTER DELETE ON likes FOR EACH ROW
BEGIN
    UPDATE posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = OLD.post_id;
END$$

CREATE TRIGGER trg_comment_insert AFTER INSERT ON comments FOR EACH ROW
BEGIN
    UPDATE posts SET comments_count = comments_count + 1 WHERE id = NEW.post_id;
END$$

CREATE TRIGGER trg_follow_insert AFTER INSERT ON follows FOR EACH ROW
BEGIN
    UPDATE users SET followers_count = followers_count + 1 WHERE id = NEW.followed_id;
    UPDATE users SET following_count = following_count + 1 WHERE id = NEW.follower_id;
END$$

DELIMITER ;