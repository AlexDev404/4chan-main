#include <unistd.h>

const char * const script = "/usr/local/etc/rc.d/01_restart-rebuildd.sh";

int main(int argc, char * argv[])
{
    const char *_argv[3] = {script, "start", NULL};
    setuid(0);
        
    return execv(_argv[0], _argv);
}
