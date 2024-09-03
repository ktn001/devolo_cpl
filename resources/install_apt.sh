BASE_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
echo BASE_DIR: $BASE_DIR

PLUGIN=$(basename "$(realpath ${BASE_DIR}/..)")
. ${BASE_DIR}/dependance.lib
. ${BASE_DIR}/pyenv.lib

launchInstall
