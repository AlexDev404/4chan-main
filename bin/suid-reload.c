#define REAL_PATH "/usr/local/nginx/sbin/nginx-reload"
    main(ac, av)
        char **av;
    {
        execv(REAL_PATH, av);
    }
