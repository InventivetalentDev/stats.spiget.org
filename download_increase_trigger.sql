BEGIN
    DECLARE bd INT;

    SET bd = (SELECT downloads FROM spiget_stats WHERE id=NEW.id ORDER BY date DESC LIMIT 0,1);

    SET NEW.downloads_incr = NEW.downloads - bd;
END
