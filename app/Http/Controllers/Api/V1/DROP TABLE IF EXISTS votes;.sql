DROP TABLE IF EXISTS votes;
DROP TABLE IF EXISTS steps;
DROP TABLE IF EXISTS topics;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS accounts;

CREATE TABLE accounts (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_profiles (
    id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    profile_picture VARCHAR(255),
    bio TEXT,
    joined_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT user_profiles_account_id_fkey FOREIGN KEY (account_id)
        REFERENCES accounts(id) ON DELETE CASCADE
);

CREATE TABLE topics (
    id SERIAL PRIMARY KEY,
    account_id INTEGER NOT NULL,
    problem VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT topics_account_id_fkey FOREIGN KEY (account_id)
        REFERENCES accounts(id) ON DELETE CASCADE
);

CREATE TABLE steps (
    id SERIAL PRIMARY KEY,
    topic_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT steps_topic_id_fkey FOREIGN KEY (topic_id)
        REFERENCES topics(id) ON DELETE CASCADE
);

CREATE TABLE votes (
    id SERIAL PRIMARY KEY,
    topic_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    is_positive BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT votes_topic_id_fkey FOREIGN KEY (topic_id)
        REFERENCES topics(id) ON DELETE CASCADE,
    CONSTRAINT votes_account_id_fkey FOREIGN KEY (account_id)
        REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT votes_topic_account_unique UNIQUE (topic_id, account_id)
);

CREATE INDEX idx_user_profiles_account_id ON user_profiles(account_id);
CREATE INDEX idx_topics_account_id ON topics(account_id);
CREATE INDEX idx_steps_topic_id ON steps(topic_id);
CREATE INDEX idx_votes_topic_id ON votes(topic_id);
CREATE INDEX idx_votes_account_id ON votes(account_id);