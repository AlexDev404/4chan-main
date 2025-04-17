#include <unistd.h>

int main(int argc, char * argv[])
{
 char *run_global = "/www/global/bin/run_global";

 argv[0] = run_global;

 return execv(run_global, argv);
}
