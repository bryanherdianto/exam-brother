-- Create sessions table
CREATE TABLE mdl_local_myplugin_sessions (
    id BIGSERIAL PRIMARY KEY,
    userid BIGINT NOT NULL,
    examname VARCHAR(255) NOT NULL,
    starttime BIGINT NOT NULL,
    endtime BIGINT,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL
);

-- Add foreign key constraint
ALTER TABLE mdl_local_myplugin_sessions
ADD CONSTRAINT mdl_locamyplsess_use_fk 
FOREIGN KEY (userid) REFERENCES mdl_user(id);

-- Create indexes
CREATE INDEX mdl_locamyplsess_sta_ix ON mdl_local_myplugin_sessions(status);
CREATE INDEX mdl_locamyplsess_sta2_ix ON mdl_local_myplugin_sessions(starttime);

-- Create alerts table
CREATE TABLE mdl_local_myplugin_alerts (
    id BIGSERIAL PRIMARY KEY,
    sessionid BIGINT NOT NULL,
    userid BIGINT NOT NULL,
    alerttype VARCHAR(50) NOT NULL,
    description TEXT,
    severity INTEGER NOT NULL DEFAULT 1,
    timecreated BIGINT NOT NULL
);

-- Add foreign key constraints
ALTER TABLE mdl_local_myplugin_alerts
ADD CONSTRAINT mdl_locamyplaler_ses_fk 
FOREIGN KEY (sessionid) REFERENCES mdl_local_myplugin_sessions(id);

ALTER TABLE mdl_local_myplugin_alerts
ADD CONSTRAINT mdl_locamyplaler_use_fk 
FOREIGN KEY (userid) REFERENCES mdl_user(id);

-- Create indexes
CREATE INDEX mdl_locamyplaler_ale_ix ON mdl_local_myplugin_alerts(alerttype);
CREATE INDEX mdl_locamyplaler_tim_ix ON mdl_local_myplugin_alerts(timecreated);

-- Create screenshots table
CREATE TABLE mdl_local_myplugin_screenshots (
    id BIGSERIAL PRIMARY KEY,
    alertid BIGINT NOT NULL,
    sessionid BIGINT NOT NULL,
    userid BIGINT NOT NULL,
    imagedata TEXT NOT NULL,
    timecreated BIGINT NOT NULL
);

-- Add foreign key constraints
ALTER TABLE mdl_local_myplugin_screenshots
ADD CONSTRAINT mdl_locamyplscre_ale_fk 
FOREIGN KEY (alertid) REFERENCES mdl_local_myplugin_alerts(id);

ALTER TABLE mdl_local_myplugin_screenshots
ADD CONSTRAINT mdl_locamyplscre_ses_fk 
FOREIGN KEY (sessionid) REFERENCES mdl_local_myplugin_sessions(id);

ALTER TABLE mdl_local_myplugin_screenshots
ADD CONSTRAINT mdl_locamyplscre_use_fk 
FOREIGN KEY (userid) REFERENCES mdl_user(id);

-- Create indexes
CREATE INDEX mdl_locamyplscre_ses_ix ON mdl_local_myplugin_screenshots(sessionid);
CREATE INDEX mdl_locamyplscre_tim_ix ON mdl_local_myplugin_screenshots(timecreated);

-- Grant permissions to your Moodle database user
GRANT ALL PRIVILEGES ON TABLE mdl_local_myplugin_sessions TO postgres;
GRANT ALL PRIVILEGES ON TABLE mdl_local_myplugin_alerts TO postgres;
GRANT ALL PRIVILEGES ON TABLE mdl_local_myplugin_screenshots TO postgres;
GRANT USAGE, SELECT ON SEQUENCE mdl_local_myplugin_sessions_id_seq TO postgres;
GRANT USAGE, SELECT ON SEQUENCE mdl_local_myplugin_alerts_id_seq TO postgres;
GRANT USAGE, SELECT ON SEQUENCE mdl_local_myplugin_screenshots_id_seq TO postgres;