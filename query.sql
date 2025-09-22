DROP DATABASE IF EXISTS movie_review_db;
CREATE DATABASE movie_review_db;
USE movie_review_db;

CREATE TABLE User (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    follower INT DEFAULT 0,
    following INT DEFAULT 0,
    type ENUM('user', 'admin') DEFAULT 'user',
    password VARCHAR(255) NOT NULL,
    subID VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Movies (
    mID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Duration INT NOT NULL, -- in minutes
    Description TEXT,
    release_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE Movies ADD COLUMN genre VARCHAR(100) DEFAULT 'Drama';

CREATE TABLE Reviews (
    reviewID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    mID INT NOT NULL,
    rating INT CHECK (rating >= 0 AND rating <= 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES User(userID) ON DELETE CASCADE,
    FOREIGN KEY (mID) REFERENCES Movies(mID) ON DELETE CASCADE,
    UNIQUE KEY unique_user_movie (userID, mID)
);


CREATE TABLE UserFollows (
    followID INT AUTO_INCREMENT PRIMARY KEY,
    followerID INT NOT NULL,
    followingID INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (followerID) REFERENCES User(userID) ON DELETE CASCADE,
    FOREIGN KEY (followingID) REFERENCES User(userID) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (followerID, followingID)
);

CREATE TABLE forum_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    post_title VARCHAR(255) NOT NULL,
    post_description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT DEFAULT NULL
);


CREATE TABLE forum_replies (
    reply_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT DEFAULT NULL, -- Optional: link to User table if you want to track who replied
    reply_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE
);



INSERT INTO Movies (Name, Duration, Description, release_date, genre) VALUES
('The Shawshank Redemption', 142, 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', '1994-09-23', 'thriller'),
('The Godfather', 175, 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.', '1972-03-24', 'Crime'),
('The Dark Knight', 152, 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests.', '2008-07-18', 'Action'),
('Pulp Fiction', 154, 'The lives of two mob hitmen, a boxer, a gangster and his wife intertwine in four tales of violence and redemption.', '1994-10-14', 'Crime'),
('Forrest Gump', 142, 'The presidencies of Kennedy and Johnson through the events of Vietnam, Watergate and other historical events unfold from the perspective of an Alabama man.', '1994-07-06', 'Drama'),
('Inception', 148, 'A thief who steals corporate secrets through dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.', '2010-07-16', 'Sci-Fi'),
('The Matrix', 136, 'A computer programmer is led to fight an underground war against powerful computers who have constructed his entire reality with a system called the Matrix.', '1999-03-31', 'Sci-Fi'),
('Goodfellas', 145, 'The story of Henry Hill and his life in the mob, covering his relationship with his wife Karen Hill and his mob partners.', '1990-09-12', 'Crime'),
('The Lion King', 88, 'A young lion prince flees his kingdom only to learn the true meaning of responsibility and bravery.', '1994-06-24', 'Animation'),
('Titanic', 195, 'A seventeen-year-old aristocrat falls in love with a kind but poor artist aboard the luxurious, ill-fated R.M.S. Titanic.', '1997-12-19', 'Romance'),
('Harakiri', 133, 'A ronin requests to commit seppuku at a feudal lord’s castle, but his tragic past reveals a deeper critique of samurai honor.', '1962-03-11', 'Drama'),
('Parasite', 132, 'A poor family infiltrates the lives of a wealthy household, but their scheme spirals into chaos when secrets are revealed.', '2019-05-30', 'Thriller'),
('Interstellar', 169, 'A former pilot joins a mission through a wormhole to find a new home for humanity as Earth becomes uninhabitable.', '2014-11-05', 'Sci-Fi'),
('Joker', 122, 'Arthur Fleck, a failed comedian, descends into madness and becomes Gotham’s infamous criminal mastermind, the Joker.', '2019-10-04', 'Crime'),
('Barbie', 114, 'Barbie leaves her perfect world of Barbieland to discover the complexities of the real world and her own identity.', '2023-07-21', 'Comedy'),
('Get Out', 104, 'A young Black man visits his white girlfriend’s family estate and uncovers a disturbing secret beneath their hospitality.', '2017-02-24', 'Horror'),
('Knives Out', 130, 'Detective Benoit Blanc investigates the death of a wealthy patriarch, unraveling family secrets and hidden motives.', '2019-11-27', 'Mystery'),
('Avengers: Infinity War', 149, 'The Avengers and their allies battle to stop Thanos from collecting the Infinity Stones and wiping out half of all life.', '2018-04-27', 'Action');


CREATE TABLE Chatroom (
    chatID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES User(userID)
);
CREATE TABLE subscriptions (
    sub_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subscription_type ENUM('monthly', 'premium') DEFAULT 'premium',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'completed',
    is_first_time BOOLEAN DEFAULT TRUE,
    auto_renew BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT (NOW()),
    updated_at DATETIME DEFAULT (NOW()),
    FOREIGN KEY (user_id) REFERENCES User(userID) ON DELETE CASCADE
);
